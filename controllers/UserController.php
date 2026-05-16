<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/controllers/HeaderController.php';
require_once dirname(__DIR__) . '/models/StudentAnalyticsRepository.php';

final class UserController
{
    public function __construct(
        private UserRepository $users = new UserRepository(),
        private HeaderController $header = new HeaderController(),
        private StudentAnalyticsRepository $analytics = new StudentAnalyticsRepository(),
    ) {
    }

    /** Unified login — admin → admin panel, student → dashboard */
    public function login(): void
    {
        if (current_user()) {
            redirect($this->postLoginPath(current_user()));
        }
        if (!empty($_SESSION['admin'])) {
            redirect('admin/dashboard.php');
        }

        $error = null;
        $email = '';
        $return = safe_return_path($_GET['return'] ?? '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = (string) ($_POST['password'] ?? '');

            $admin = (new AdminRepository())->verifyAdmin($email, $password);
            if ($admin) {
                session_regenerate_id(true);
                $_SESSION['admin'] = $admin;
                unset($_SESSION['user']);
                redirect('admin/dashboard.php');
            }

            $student = $this->users->verifyLogin($email, $password);
            if ($student) {
                session_regenerate_id(true);
                $_SESSION['user'] = $student;
                unset($_SESSION['admin']);
                $this->users->touchLastLogin((int) $student['id']);
                $return = safe_return_path($_POST['return'] ?? $_GET['return'] ?? '');
                redirect($return !== '' ? ltrim($return, '/') : 'dashboard.php');
            }

            $error = 'Invalid email or password.';
        }

        $header = $this->header->build('login', null);
        $pageTitle = 'Login | Acharya Books';
        require dirname(__DIR__) . '/includes/public/layout_start.php';
        require dirname(__DIR__) . '/includes/public/views/login_form.php';
        require dirname(__DIR__) . '/includes/public/layout_end.php';
    }

    public function register(): void
    {
        if (current_user()) {
            redirect('dashboard.php');
        }

        $error = null;
        $form = ['name' => '', 'email' => '', 'phone' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form = [
                'name' => trim($_POST['name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
            ];
            $password = (string) ($_POST['password'] ?? '');
            $confirm = (string) ($_POST['password_confirm'] ?? '');

            try {
                if ($password !== $confirm) {
                    throw new InvalidArgumentException('Passwords do not match.');
                }
                $id = $this->users->registerStudent($form['name'], $form['email'], $form['phone'], $password);
                $user = $this->users->findById($id);
                if ($user) {
                    session_regenerate_id(true);
                    $_SESSION['user'] = $user;
                    redirect('dashboard.php');
                }
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $header = $this->header->build('register', null);
        $pageTitle = 'Register | Acharya Books';
        require dirname(__DIR__) . '/includes/public/layout_start.php';
        require dirname(__DIR__) . '/includes/public/views/register_form.php';
        require dirname(__DIR__) . '/includes/public/layout_end.php';
    }

    public function dashboard(): void
    {
        $user = require_login();
        $data = $this->analytics->dashboard((int) $user['id']);
        $header = $this->header->build('dashboard', null);
        $pageTitle = 'Student Dashboard | Acharya Books';
        require dirname(__DIR__) . '/includes/public/layout_start.php';
        require dirname(__DIR__) . '/includes/public/views/student_dashboard.php';
        require dirname(__DIR__) . '/includes/public/layout_end.php';
    }

    /** @param array<string,mixed> $user */
    private function postLoginPath(array $user): string
    {
        if (($user['role'] ?? '') === 'admin') {
            return 'admin/dashboard.php';
        }

        return 'dashboard.php';
    }
}
