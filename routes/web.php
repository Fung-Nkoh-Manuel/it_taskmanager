<?php

// ─── Auth ────────────────────────────────────────────────────────────────────
Router::get( '/login',          'AuthController', 'loginForm');
Router::post('/login',          'AuthController', 'login');
Router::get( '/register',       'AuthController', 'registerForm');
Router::post('/register',       'AuthController', 'register');
Router::get( '/logout',         'AuthController', 'logout');

// ─── Dashboard ───────────────────────────────────────────────────────────────
Router::get('/',          'DashboardController', 'index');
Router::get('/dashboard', 'DashboardController', 'index');

// ─── Tasks ───────────────────────────────────────────────────────────────────
Router::get( '/tasks',                                  'TaskController', 'index');
Router::get( '/tasks/create',                           'TaskController', 'create');
Router::post('/tasks',                                  'TaskController', 'store');
Router::get( '/tasks/{id}',                             'TaskController', 'show');
Router::get( '/tasks/{id}/edit',                        'TaskController', 'edit');
Router::post('/tasks/{id}',                             'TaskController', 'update');
Router::post('/tasks/{id}/delete',                      'TaskController', 'destroy');
Router::post('/tasks/{id}/comment',                     'TaskController', 'addComment');
Router::post('/tasks/{id}/upload',                      'TaskController', 'uploadAttachment');
Router::post('/tasks/{id}/delete-attachment/{attId}',   'TaskController', 'deleteAttachment');

// ─── Calendar ────────────────────────────────────────────────────────────────
Router::get('/calendar',        'CalendarController', 'index');
Router::get('/calendar/events', 'CalendarController', 'events');

// ─── Users (admin) ───────────────────────────────────────────────────────────
Router::get( '/users',                  'UserController', 'index');
Router::get( '/users/create',           'UserController', 'create');
Router::post('/users',                  'UserController', 'store');
Router::get( '/users/{id}/edit',        'UserController', 'edit');
Router::post('/users/{id}',             'UserController', 'update');
Router::post('/users/{id}/delete',      'UserController', 'destroy');
Router::post('/users/{id}/toggle',      'UserController', 'toggle');

// ─── Notifications ───────────────────────────────────────────────────────────
Router::get( '/notifications',              'NotificationController', 'index');
Router::post('/notifications/read-all',     'NotificationController', 'readAll');
Router::post('/notifications/read/{id}',    'NotificationController', 'markRead');

// ─── Profile ─────────────────────────────────────────────────────────────────
Router::get( '/profile',          'ProfileController', 'index');
Router::post('/profile',          'ProfileController', 'update');
Router::post('/profile/password', 'ProfileController', 'updatePassword');

// ─── Logs (admin) ────────────────────────────────────────────────────────────
Router::get('/logs', 'LogController', 'index');

// ─── Subtasks ────────────────────────────────────────────────────────────────
Router::post('/tasks/{taskId}/subtasks',                        'SubtaskController', 'store');
Router::post('/tasks/{taskId}/subtasks/{id}/complete',          'SubtaskController', 'complete');
Router::post('/tasks/{taskId}/subtasks/{id}/reopen',            'SubtaskController', 'reopen');
Router::post('/tasks/{taskId}/subtasks/{id}/delete',            'SubtaskController', 'destroy');

// ─── API ─────────────────────────────────────────────────────────────────────
Router::get( '/api/tasks',              'ApiController', 'index');
Router::get( '/api/tasks/{id}',         'ApiController', 'show');
Router::post('/api/tasks',              'ApiController', 'store');
Router::post('/api/tasks/{id}',         'ApiController', 'update');
Router::post('/api/tasks/{id}/delete',  'ApiController', 'destroy');
Router::post('/api/tasks/{id}/status',  'ApiController', 'updateStatus');