<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

function api_response(array $data, int $status = 200): void
{
	http_response_code($status);
	echo json_encode($data, JSON_THROW_ON_ERROR);
	exit;
}

function api_input(): array
{
	try {
		$input = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
	} catch (JsonException) {
		api_response(['error' => 'Request body must be valid JSON.'], 400);
	}

	if (!is_array($input)) {
		api_response(['error' => 'Request body must be a JSON object.'], 400);
	}

	return $input;
}

function api_user(bool $adminOnly = false): array
{
	if (!isset($_SESSION['user_id'])) {
		api_response(['error' => 'Sign in is required.'], 401);
	}

	try {
		$statement = database()->prepare('SELECT id, name, email, role FROM users WHERE id = :id LIMIT 1');
		$statement->execute(['id' => (int) $_SESSION['user_id']]);
		$user = $statement->fetch();
	} catch (PDOException $exception) {
		error_log($exception->getMessage());
		api_response(['error' => 'The service is temporarily unavailable.'], 503);
	}

	if (!$user) {
		api_response(['error' => 'Your session is no longer valid. Sign in again.'], 401);
	}
	if ($adminOnly && $user['role'] !== 'admin') {
		api_response(['error' => 'Administrator access is required.'], 403);
	}

	$_SESSION['user_name'] = $user['name'];
	$_SESSION['user_role'] = $user['role'];
	return $user;
}

function api_require_method(array $methods): void
{
	if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
		header('Allow: ' . implode(', ', $methods));
		api_response(['error' => 'Method not allowed.'], 405);
	}
}

function api_require_csrf(): void
{
	if (!valid_csrf_token()) {
		api_response(['error' => 'Your session expired. Refresh the page and try again.'], 403);
	}
}

function api_require_admin_reauth(): void
{
	$verifiedAt = $_SESSION['admin_reauthenticated_at'] ?? null;
	if (!is_int($verifiedAt) || $verifiedAt > time() || time() - $verifiedAt > 600) {
		api_response(['error' => 'Confirm your administrator email and password before making changes.'], 403);
	}
}

function api_id(mixed $value): ?int
{
	$id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
	return $id === false ? null : $id;
}

function api_text(mixed $value): string
{
	return is_string($value) || is_numeric($value) ? trim((string) $value) : '';
}

function api_database_error(PDOException $exception): void
{
	error_log($exception->getMessage());
	$status = $exception->getCode() === '23000' ? 409 : 500;
	$isReferencedRecord = $status === 409 && (int) ($exception->errorInfo[1] ?? 0) === 1451;
	api_response([
		'error' => $status === 409
			? ($isReferencedRecord
				? 'This option is in use. Remove its related courses, subjects, or grades before deleting it.'
				: 'A record with the same unique value already exists.')
			: 'The request could not be completed. Please try again later.',
	], $status);
}
