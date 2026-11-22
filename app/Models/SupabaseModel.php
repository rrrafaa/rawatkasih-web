<?php

namespace App\Models;

use App\Exceptions\SupabaseUnavailable;
use App\Services\SupabaseClient;

/**
 * Read foundation for modules. All data access uses the caller's JWT and RLS.
 * Add writes only with FormRequest, Policy and domain transaction/RPC tests.
 */
abstract class SupabaseModel
{
    protected string $table;

    protected string $columns;

    protected string $keyType = 'uuid';

    public function __construct(protected SupabaseClient $client) {}

    /** @return list<array<string, mixed>> */
    public function forCurrentUser(int $page = 1): array
    {
        return $this->read(['limit' => 25, 'offset' => (max(1, $page) - 1) * 25]);
    }

    /** @return array<string, mixed>|null */
    public function findForCurrentUser(string $id): ?array
    {
        if (! preg_match($this->keyType === 'uuid'
            ? '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD'
            : '/^[1-9][0-9]*$/D', $id)) {
            return null;
        }

        return $this->read(['id' => 'eq.'.$id, 'limit' => 1])[0] ?? null;
    }

    /** @param array<string, mixed> $query
     * @return list<array<string, mixed>>
     */
    protected function read(array $query): array
    {
        abort_unless(auth()->user() instanceof SupabaseUser, 401);
        $token = request()->session()->get('supabase.access_token');
        abort_unless(is_string($token) && $token !== '', 401);
        $response = $this->client->request('GET', '/rest/v1/'.$this->table, [
            'select' => $this->columns, 'order' => 'id.asc', ...$query,
        ], $token);
        abort_if(in_array($response->status(), [401, 403], true), 403);
        if (! $response->successful()) {
            \Log::error('Supabase query failed', [
        'table'  => $this->table,
        'status' => $response->status(),
        'body'   => $response->body(),
    ]);
            throw new SupabaseUnavailable;
        }

        return $response->json();
    }
}
