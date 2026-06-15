<?php

namespace App\Support;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Throwable;

class Feedback
{
    /**
     * @param  Closure(): array{metadata?: array{code?: string, message?: string}}  $action
     */
    public static function metadata(Closure $action, string $failureMessage, ?Closure $successMessage = null): RedirectResponse
    {
        try {
            $result = $action();
            $code = (string) Arr::get($result, 'metadata.code', '');

            return back()->with('toast', [
                'type' => $code === '200' ? 'success' : 'error',
                'message' => $code === '200' && $successMessage !== null
                    ? $successMessage($result)
                    : (string) Arr::get($result, 'metadata.message', 'Respons layanan tidak memiliki pesan.'),
            ]);
        } catch (ValidationException $exception) {
            return self::validationResponse($exception, $failureMessage);
        } catch (QueryException $exception) {
            return self::queryResponse($exception, $failureMessage);
        } catch (Throwable $exception) {
            return self::throwableResponse($exception, $failureMessage);
        }
    }

    /**
     * @param  Closure(): array{berhasil: bool, pesan: string}  $action
     */
    public static function hasil(Closure $action, string $failureMessage): RedirectResponse
    {
        try {
            $result = $action();

            return back()->with('toast', [
                'type' => $result['berhasil'] ? 'success' : 'error',
                'message' => $result['pesan'],
            ]);
        } catch (ValidationException $exception) {
            return self::validationResponse($exception, $failureMessage);
        } catch (QueryException $exception) {
            return self::queryResponse($exception, $failureMessage);
        } catch (Throwable $exception) {
            return self::throwableResponse($exception, $failureMessage);
        }
    }

    public static function mutasi(Closure $action, string $successMessage, string $failureMessage): RedirectResponse
    {
        try {
            $message = $action();

            return back()->with('toast', [
                'type' => 'success',
                'message' => is_string($message) && filled($message) ? $message : $successMessage,
            ]);
        } catch (ValidationException $exception) {
            return self::validationResponse($exception, $failureMessage);
        } catch (QueryException $exception) {
            return self::queryResponse($exception, $failureMessage);
        } catch (Throwable $exception) {
            return self::throwableResponse($exception, $failureMessage);
        }
    }

    private static function validationResponse(ValidationException $exception, string $failureMessage): RedirectResponse
    {
        return back()
            ->withErrors($exception->errors())
            ->withInput()
            ->with('toast', [
                'type' => 'error',
                'message' => self::validationMessage($exception, $failureMessage),
            ]);
    }

    private static function queryResponse(QueryException $exception, string $failureMessage): RedirectResponse
    {
        report($exception);

        return back()
            ->withInput()
            ->with('toast', [
                'type' => 'error',
                'message' => self::queryMessage($exception, $failureMessage),
            ]);
    }

    private static function throwableResponse(Throwable $exception, string $failureMessage): RedirectResponse
    {
        report($exception);

        return back()
            ->withInput()
            ->with('toast', [
                'type' => 'error',
                'message' => $failureMessage.' Terjadi kesalahan sistem. Coba ulangi, lalu hubungi administrator jika masih gagal.',
            ]);
    }

    private static function validationMessage(ValidationException $exception, string $failureMessage): string
    {
        $firstError = Arr::first(Arr::flatten($exception->errors()));

        if (filled($firstError)) {
            return $failureMessage.' '.$firstError;
        }

        return $failureMessage.' Periksa kembali isian yang belum sesuai.';
    }

    private static function queryMessage(QueryException $exception, string $failureMessage): string
    {
        $driverErrorCode = (int) ($exception->errorInfo[1] ?? 0);

        $detail = match ($driverErrorCode) {
            1048 => 'Ada data wajib yang belum terisi. Periksa kembali form sebelum menyimpan.',
            1062 => 'Kode atau data unik yang sama sudah digunakan. Gunakan nilai lain.',
            1406 => 'Ada isian yang terlalu panjang untuk kolom database. Persingkat data lalu coba lagi.',
            1451 => 'Data masih dipakai oleh data lain sehingga belum dapat dihapus.',
            1452 => 'Data referensi tidak valid atau sudah tidak tersedia. Muat ulang halaman lalu pilih data kembali.',
            default => 'Terjadi masalah database saat memproses data. Coba ulangi, lalu hubungi administrator jika masih gagal.',
        };

        return $failureMessage.' '.$detail;
    }
}
