<?php

namespace App\Services;

use App\Exceptions\PersonnelApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PersonnelApiService
{
    /**
     * @return list<array{id: int, first_name: string, last_name: string, email: string, department: string}>
     */
    public function employees(): array
    {
        $response = $this->request('/api/employees');
        $this->ensureSuccessful($response);
        $data = $response->json('data');

        if (! is_array($data) || ! array_is_list($data)) {
            throw new PersonnelApiException('Personel Servisi geçersiz personel verisi döndürdü.', 502);
        }

        return array_map(fn (mixed $employee): array => $this->validateEmployee($employee), $data);
    }

    /**
     * @return array{id: int, first_name: string, last_name: string, email: string, department: string}|null
     */
    public function employee(int $id): ?array
    {
        $response = $this->request('/api/employees/'.$id);

        if ($response->status() === 404) {
            return null;
        }

        $this->ensureSuccessful($response);
        $employee = $this->validateEmployee($response->json('data'));

        if ($employee['id'] !== $id) {
            throw new PersonnelApiException('Personel Servisi geçersiz personel verisi döndürdü.', 502);
        }

        return $employee;
    }

    private function request(string $path): Response
    {
        $baseUrl = rtrim((string) config('services.personnel_service.url'), '/');
        $token = config('services.personnel_service.token');

        if ($baseUrl === '' || ! is_string($token) || $token === '') {
            throw new PersonnelApiException('Personel Servisi bağlantı ayarları eksik. Lütfen yapılandırmayı kontrol edin.', 503);
        }

        try {
            return Http::baseUrl($baseUrl)
                ->withToken($token)
                ->acceptJson()
                ->timeout(5)
                ->get($path);
        } catch (ConnectionException $exception) {
            throw new PersonnelApiException('Personel Servisi şu anda erişilemiyor. Lütfen daha sonra tekrar deneyin.', 503, $exception);
        }
    }

    private function ensureSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $message = match ($response->status()) {
            401 => 'Personel Servisi kimlik doğrulaması başarısız oldu. API token yapılandırmasını kontrol edin.',
            404 => 'Personel Servisi istenen kaydı bulamadı.',
            default => $response->serverError()
                ? 'Personel Servisi geçici olarak hizmet veremiyor. Daha sonra tekrar deneyin.'
                : 'Personel Servisi beklenmeyen bir yanıt verdi.',
        };

        throw new PersonnelApiException($message, $response->status() >= 500 ? 503 : 502);
    }

    /**
     * @return array{id: int, first_name: string, last_name: string, email: string, department: string}
     */
    private function validateEmployee(mixed $employee): array
    {
        if (! is_array($employee)) {
            throw new PersonnelApiException('Personel Servisi geçersiz personel verisi döndürdü.', 502);
        }

        $id = $employee['id'] ?? null;
        $firstName = $employee['first_name'] ?? null;
        $lastName = $employee['last_name'] ?? null;
        $email = $employee['email'] ?? null;
        $department = $employee['department'] ?? null;

        if (
            ! is_int($id)
            || $id < 1
            || ! is_string($firstName)
            || $firstName === ''
            || ! is_string($lastName)
            || $lastName === ''
            || ! is_string($email)
            || $email === ''
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || ! is_string($department)
            || $department === ''
        ) {
            throw new PersonnelApiException('Personel Servisi geçersiz personel verisi döndürdü.', 502);
        }

        return [
            'id' => $id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'department' => $department,
        ];
    }
}
