@props([
    'updates' => [],
    'emptyMessage' => 'Belum ada kabar terbaru yang dibagikan.',
])

<div class="rk-timeline-feed" aria-label="Timeline Kabar Keluarga">
    @if (empty($updates))
        <div class="rk-timeline-empty" style="background: #ffffff; border: 1px dashed #d1d5db; border-radius: 12px; padding: 40px 20px; text-align: center;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 40px; height: 40px; color: #9ca3af; margin: 0 auto 12px;" aria-hidden="true">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8Z"></path>
            </svg>
            <p style="font-size: 0.9375rem; color: #4b5563; font-weight: 500; margin: 0;">{{ $emptyMessage }}</p>
        </div>
    @else
        <div class="rk-timeline-list" style="display: flex; flex-direction: column; gap: 20px;">
            @foreach ($updates as $update)
                @php
                    $isPublished = ($update['status'] ?? '') === 'published';
                    $timeSource = $isPublished ? ($update['published_at'] ?? $update['created_at']) : ($update['created_at'] ?? null);
                    $carbonTime = filled($timeSource)
                        ? \Carbon\Carbon::parse($timeSource)->locale('id')
                        : null;
                    $timeFormatted = $carbonTime
                        ? $carbonTime->isoFormat('D MMMM Y, HH:mm') . ' WIB'
                        : '-';
                    $photoUrl = $update['photo_url'] ?? null;
                @endphp

                <article class="rk-timeline-item" style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    @if ($photoUrl)
                        <div class="rk-timeline-media" style="width: 100%; max-height: 380px; overflow: hidden; background: #f3f4f6;">
                            <img
                                src="{{ $photoUrl }}"
                                alt="Dokumentasi aktivitas {{ $update['resident_name'] ?? 'Lansia' }}"
                                style="width: 100%; height: auto; max-height: 380px; object-fit: cover; display: block;"
                                loading="lazy"
                            >
                        </div>
                    @endif

                    <div class="rk-timeline-body" style="padding: 18px 20px;">
                        <div class="rk-timeline-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px;">
                            <div>
                                <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin: 0;">
                                    {{ $update['resident_name'] ?? 'Lansia' }}
                                    @if (filled($update['resident_room'] ?? null))
                                        <span style="font-size: 0.8125rem; font-weight: normal; color: #6b7280;">(Kamar {{ $update['resident_room'] }})</span>
                                    @endif
                                </h3>
                                <time datetime="{{ $timeSource }}" style="font-size: 0.8125rem; color: #6b7280; display: block; margin-top: 2px;">
                                    {{ $timeFormatted }}
                                </time>
                            </div>

                            @if (! $isPublished)
                                <span style="display: inline-block; padding: 3px 8px; font-size: 0.75rem; font-weight: 600; border-radius: 6px; background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                                    Draft
                                </span>
                            @else
                                <span style="display: inline-block; padding: 3px 8px; font-size: 0.75rem; font-weight: 600; border-radius: 6px; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;">
                                    Terbit
                                </span>
                            @endif
                        </div>

                        <div class="rk-timeline-content" style="font-size: 0.9375rem; line-height: 1.6; color: #374151; white-space: pre-line;">
                            {{ $update['summary'] }}
                        </div>

                        @if (! $isPublished && auth()->user() && auth()->user()->id() === ($update['author_id'] ?? null))
                            <div class="rk-timeline-actions" style="margin-top: 14px; display: flex; gap: 8px; justify-content: flex-end;">
                                <a
                                    href="{{ route('care-updates.family-updates.edit', ['updateId' => $update['id']]) }}"
                                    class="rk-action-btn rk-action-edit"
                                    style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; font-size: 0.8125rem; font-weight: 500; color: #0d9488; background: #f0fdfa; border: 1px solid #ccfbf1; border-radius: 6px; text-decoration: none;"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;" aria-hidden="true">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    Edit Draf
                                </a>
                                <form
                                    method="POST"
                                    action="{{ route('care-updates.family-updates.destroy', ['updateId' => $update['id']]) }}"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus draf kabar keluarga ini?');"
                                    style="display: inline;"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="rk-action-btn rk-action-delete"
                                        style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; font-size: 0.8125rem; font-weight: 500; color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; cursor: pointer;"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;" aria-hidden="true">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        @endif

                        <footer class="rk-timeline-footer" style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 12px; border-top: 1px solid #f3f4f6; font-size: 0.75rem; color: #9ca3af;">
                            <span>Dibagikan oleh: <strong>{{ $update['author_name'] ?? 'Caregiver Panti' }}</strong></span>
                            @if ($photoUrl)
                                <span style="display: inline-flex; align-items: center; gap: 4px; color: #0d9488;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;" aria-hidden="true">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                    Dokumentasi berizin
                                </span>
                            @endif
                        </footer>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
