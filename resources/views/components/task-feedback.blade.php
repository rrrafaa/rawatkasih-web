@php
    $taskFeedback = session('task_feedback');

    // Jika validasi Laravel gagal, tampilkan feedback error.
    if (!is_array($taskFeedback) && $errors->any()) {
        $taskFeedback = [
            'type' => 'error',
            'title' => 'Input belum berhasil diproses',
            'message' => $errors->first(),
        ];
    }

    $showFeedback = is_array($taskFeedback);

    $feedbackType = $showFeedback
        && ($taskFeedback['type'] ?? '') === 'success'
            ? 'success'
            : 'error';

    $feedbackTitle = $showFeedback
        ? ($taskFeedback['title'] ?? 'Terjadi kesalahan')
        : '';

    $feedbackMessage = $showFeedback
        ? ($taskFeedback['message'] ?? '')
        : '';
@endphp

<dialog
    id="rk-task-feedback-dialog"
    class="rk-task-feedback-dialog"
    data-type="{{ $feedbackType }}"
    data-show-on-load="{{ $showFeedback ? 'true' : 'false' }}"
    aria-labelledby="rk-task-feedback-title"
    aria-describedby="rk-task-feedback-message"
>
    <div
        class="rk-task-feedback-symbol"
        id="rk-task-feedback-dialog-symbol"
        aria-hidden="true"
    >
        {{ $feedbackType === 'success' ? '✓' : '!' }}
    </div>

    <h2 id="rk-task-feedback-title">
        {{ $feedbackTitle }}
    </h2>

    <p id="rk-task-feedback-message">
        {{ $feedbackMessage }}
    </p>
</dialog>

<div
    id="rk-task-feedback-toast"
    class="rk-task-feedback-toast"
    data-type="{{ $feedbackType }}"
    role="status"
    aria-live="polite"
    hidden
>
    <div
        class="rk-task-feedback-toast-symbol"
        id="rk-task-feedback-toast-symbol"
        aria-hidden="true"
    >
        {{ $feedbackType === 'success' ? '✓' : '!' }}
    </div>

    <div class="rk-task-feedback-toast-content">
        <strong id="rk-task-feedback-toast-title">
            {{ $feedbackTitle }}
        </strong>

        <p id="rk-task-feedback-toast-message">
            {{ $feedbackMessage }}
        </p>
    </div>

    <button
        type="button"
        id="rk-task-feedback-toast-close"
        aria-label="Tutup notifikasi"
    >
        &times;
    </button>
</div>