<?php
require_once __DIR__ . '/functions.php';

function current_member(): ?array
{
    if (empty($_SESSION['member_id'])) {
        return null;
    }
    static $member = null;
    if ($member === null) {
        $stmt = db_app()->prepare('SELECT * FROM app_members WHERE id = ? AND status = "active"');
        $stmt->execute([$_SESSION['member_id']]);
        $member = $stmt->fetch() ?: false;
    }
    return $member ?: null;
}

function require_login(): array
{
    $member = current_member();
    if (!$member) {
        flash_set('error', 'Silakan login terlebih dahulu.');
        redirect('login.php');
    }
    return $member;
}

function login_member(string $usernameOrEmail, string $password): ?array
{
    $stmt = db_app()->prepare(
        'SELECT * FROM app_members WHERE (username = ? OR email = ?) AND status = "active" LIMIT 1'
    );
    $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
    $member = $stmt->fetch();

    if ($member && password_verify($password, $member['password_hash'])) {
        $_SESSION['member_id'] = $member['id'];
        return $member;
    }
    return null;
}

function logout_member(): void
{
    unset($_SESSION['member_id']);
    session_regenerate_id(true);
}

// ---------------- Admin auth (terpisah dari member) ----------------

function current_admin(): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    static $admin = null;
    if ($admin === null) {
        $stmt = db_app()->prepare('SELECT * FROM admins WHERE id = ?');
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch() ?: false;
    }
    return $admin ?: null;
}

function require_admin(): array
{
    $admin = current_admin();
    if (!$admin) {
        redirect('login.php');
    }
    return $admin;
}

function login_admin(string $username, string $password): ?array
{
    $stmt = db_app()->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        return $admin;
    }
    return null;
}

function logout_admin(): void
{
    unset($_SESSION['admin_id']);
    session_regenerate_id(true);
}
