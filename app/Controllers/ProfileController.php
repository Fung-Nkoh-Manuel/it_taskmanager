<?php

class ProfileController extends BaseController
{
    private UserModel $users;

    public function __construct()
    {
        $this->users = new UserModel();
    }

    public function index(): void
    {
        AuthMiddleware::require();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        $this->view('profile/index', [
            'user'   => $this->getCurrentUser(),
            'tab'    => $_GET['tab'] ?? 'profile',
            'flash'  => $flash,
            'errors' => [],
        ]);
    }

    public function update(): void
    {
        AuthMiddleware::require();
        AuthMiddleware::verifyCsrf();

        $id   = $_SESSION['user_id'];
        $user = $this->getCurrentUser();

        $data   = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email'     => trim($_POST['email']      ?? ''),
        ];
        $errors = [];

        if (empty($data['full_name'])) {
            $errors['full_name'] = 'Full name is required.';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email is required.';
        }
        if (empty($errors['email']) && $this->users->findByEmail($data['email'], $id)) {
            $errors['email'] = 'This email is already in use.';
        }

        // Handle avatar upload
        if (!empty($_FILES['avatar']['name'])) {
            $file  = $_FILES['avatar'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);

            if (!in_array($mime, ['image/jpeg','image/png','image/webp','image/gif'], true)) {
                $errors['avatar'] = 'Only JPG, PNG, WEBP or GIF images are allowed.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $errors['avatar'] = 'Image must be under 2 MB.';
            } else {
                $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
                $name = 'avatar_' . $id . '_' . time() . '.' . strtolower($ext);
                move_uploaded_file($file['tmp_name'], UPLOAD_PATH . '/' . $name);
                $data['avatar'] = $name;
            }
        }

        if (!empty($errors)) {
            $this->view('profile/index', [
                'user'   => array_merge($user, $data),
                'tab'    => 'profile',
                'errors' => $errors,
            ]);
            return;
        }

        $this->users->update($id, $data);
        $this->flash('success', 'Profile updated successfully.');
        $this->redirect('/profile');
    }

    public function updatePassword(): void
    {
        AuthMiddleware::require();
        AuthMiddleware::verifyCsrf();

        $id   = $_SESSION['user_id'];
        $user = $this->getCurrentUser();

        $current  = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password']      ?? '';
        $confirm  = $_POST['confirm_password']  ?? '';
        $errors   = [];

        if (!$this->users->verifyPassword($current, $user['password'])) {
            $errors['current_password'] = 'Current password is incorrect.';
        }
        if (strlen($new) < 6) {
            $errors['new_password'] = 'New password must be at least 6 characters.';
        }
        if ($new !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            $this->view('profile/index', [
                'user'   => $user,
                'tab'    => 'password',
                'errors' => $errors,
            ]);
            return;
        }

        $this->users->update($id, ['password' => $new]);
        $this->flash('success', 'Password changed successfully.');
        $this->redirect('/profile?tab=password');
    }
}
