<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Auth;
use Gaia\Admin\Csrf;
use Gaia\Admin\Session;
use Gaia\Admin\View;

final class AuthController
{
    public function loginForm(array $params = []): void
    {
        if (Auth::check()) {
            redirect('');
        }

        View::render('login', ['title' => 'Accedi'], null);
    }

    public function login(array $params = []): void
    {
        Csrf::verify();

        $email = (string) ($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $error = Auth::attempt($email, $password);

        if ($error !== null) {
            Session::flash('error', $error);
            redirect('login');
        }

        // Dopo il login torna dove stava andando.
        $intended = Session::pull('_intended');
        redirect(is_string($intended) && $intended !== '' ? $intended : admin_url());
    }

    public function logout(array $params = []): void
    {
        Csrf::verify();
        Auth::logout();

        redirect('login');
    }
}
