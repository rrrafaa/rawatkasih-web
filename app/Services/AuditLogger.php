<?php

namespace App\Services;

use App\Models\SupabaseUser;

/**
 * Helper service untuk pencatatan audit logs yang append-only.
 *
 * Catatan: Tidak ada transaksi DB Laravel karena semua data di Supabase.
 * Untuk operasi multiple writes yang butuh konsistensi, gunakan RPC/PostgREST transaction.
 */
class AuditLogger
{
    protected SupabaseClient $client;
    protected ?string $token;

    public function __construct(SupabaseClient $client, ?string $token = null)
    {
        $this->client = $client;
        $this->token = $token;
    }

    /**
     * Catat audit log untuk sebuah aksi.
     *
     * @param  string  $action  Action yang dilakukan (create, update, delete, status_change)
     * @param  string  $targetType  Tipe model target (Incident, CareTask, dll)
     * @param  int|string  $targetId  ID dari record target
     */
    public function log(string $action, string $targetType, int|string $targetId): void
    {
        $actor = auth()->user();

        if (! $actor instanceof SupabaseUser) {
            abort(401, 'User tidak terotentikasi');
        }

        // Gunakan token yang diberikan atau ambil dari session
        $token = $this->token ?? request()->session()->get('supabase.access_token');

        $this->writeLog($actor, $action, $targetType, $targetId, $token);
    }

    /**
     * Tulis log audit ke database.
     */
    protected function writeLog(SupabaseUser $actor, string $action, string $targetType, int|string $targetId, ?string $token = null): void
    {
        $this->client->request(
            'POST',
            '/rest/v1/audit_logs',
            [
                'actor_id' => $actor->id(),
                'action' => $action,
                'target_table' => $targetType,
                'target_id' => (string) $targetId,
                'occurred_at' => now()->toISOString(),
            ],
            $token
        );
    }
}