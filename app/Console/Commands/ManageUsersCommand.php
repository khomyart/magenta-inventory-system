<?php

namespace App\Console\Commands;

use App\Models\Allowense;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ManageUsersCommand extends Command
{
    protected $signature = 'user:manage';
    protected $description = 'Інтерактивне управління користувачами, ролями та дозволами';

    public function handle(): int
    {
        // Встановлюємо UTF-8 кодування для Windows консолі
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Спробуємо встановити UTF-8 кодування
            exec('chcp 65001');
        }

        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║   Управління користувачами, ролями та дозволами      ║');
        $this->info('╚══════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->mainMenu();

        return Command::SUCCESS;
    }

    /**
     * Конвертує введення з консольного кодування в UTF-8 (для Windows)
     */
    protected function convertEncoding(string $input): string
    {
        if (empty($input)) {
            return $input;
        }

        // Тільки для Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            return $input;
        }

        // Перевіряємо чи потрібна конвертація
        if (mb_check_encoding($input, 'UTF-8')) {
            return $input;
        }

        // Спробуємо конвертувати з CP1251 (Windows Cyrillic)
        $converted = @iconv('CP1251', 'UTF-8//IGNORE', $input);
        if ($converted !== false && !empty($converted)) {
            return $converted;
        }

        // Спробуємо CP866 (DOS Cyrillic)
        $converted = @iconv('CP866', 'UTF-8//IGNORE', $input);
        if ($converted !== false && !empty($converted)) {
            return $converted;
        }

        // Якщо не вдалося конвертувати, повертаємо оригінал
        return $input;
    }

    /**
     * Перевизначаємо ask() для автоматичної конвертації кодування
     */
    public function ask($question, $default = null)
    {
        $answer = parent::ask($question, $default);
        return $this->convertEncoding($answer ?? '');
    }

    /**
     * Перевизначаємо secret() для автоматичної конвертації кодування
     */
    public function secret($question, $fallback = true)
    {
        $answer = parent::secret($question, $fallback);
        return $this->convertEncoding($answer ?? '');
    }

    /**
     * Вибір з нумерованими пунктами (починаючи з 1)
     * Повертає обране значення
     */
    protected function numberedChoice(string $question, array $options): ?string
    {
        // Створюємо масив з номерами
        $numberedOptions = [];
        $index = 1;
        foreach ($options as $option) {
            $numberedOptions[$index] = "{$index}. {$option}";
            $index++;
        }

        $selected = $this->choice($question, $numberedOptions);

        // Витягуємо номер з вибраного рядка "N. Text"
        if (preg_match('/^(\d+)\.\s+(.+)$/', $selected, $matches)) {
            $number = (int)$matches[1];
            return $options[$number - 1] ?? null;
        }

        return null;
    }

    // ==================== ГОЛОВНЕ МЕНЮ ====================

    protected function mainMenu(): void
    {
        do {
            $choice = $this->numberedChoice(
                'Оберіть розділ',
                [
                    'Користувачі',
                    'Ролі',
                    'Дозволи',
                    'Вихід',
                ]
            );

            $this->newLine();

            switch ($choice) {
                case 'Користувачі':
                    $this->usersMenu();
                    break;
                case 'Ролі':
                    $this->rolesMenu();
                    break;
                case 'Дозволи':
                    $this->allowensesMenu();
                    break;
                case 'Вихід':
                    $this->info('До побачення!');
                    return;
            }

            $this->newLine();
        } while (true);
    }

    // ==================== МЕНЮ КОРИСТУВАЧІВ ====================

    protected function usersMenu(): void
    {
        do {
            $choice = $this->numberedChoice(
                'Користувачі - оберіть дію',
                [
                    'Відобразити перелік',
                    'Створити користувача',
                    'Редагувати користувача',
                    'Видалити користувача',
                    'Змінити пароль',
                    'Присвоїти роль',
                    'Видалити роль від користувача',
                    'Назад',
                ]
            );

            $this->newLine();

            switch ($choice) {
                case 'Відобразити перелік':
                    $this->listUsers();
                    break;
                case 'Створити користувача':
                    $this->createUser();
                    break;
                case 'Редагувати користувача':
                    $this->editUser();
                    break;
                case 'Видалити користувача':
                    $this->deleteUser();
                    break;
                case 'Змінити пароль':
                    $this->changeUserPassword();
                    break;
                case 'Присвоїти роль':
                    $this->assignRoleToUser();
                    break;
                case 'Видалити роль від користувача':
                    $this->removeRoleFromUser();
                    break;
                case 'Назад':
                    return;
            }

            $this->newLine();
        } while (true);
    }

    protected function listUsers(): void
    {
        $users = User::with('roles')->get();

        if ($users->isEmpty()) {
            $this->warn('Користувачів не знайдено');
            return;
        }

        $data = $users->map(function ($user) {
            return [
                'ID' => $user->id,
                'Ім\'я' => $user->name,
                'Email' => $user->email,
                'Ролі' => $user->roles->pluck('name')->join(', ') ?: '-',
            ];
        })->toArray();

        $this->table(['ID', 'Ім\'я', 'Email', 'Ролі'], $data);
    }

    protected function createUser(): void
    {
        $this->info('Створення нового користувача');

        // Email
        do {
            $email = $this->ask('Email');
            $emailValidation = $this->validateEmail($email);
            if ($emailValidation !== true) {
                $this->error($emailValidation);
                continue;
            }
            break;
        } while (true);

        // Ім'я
        do {
            $name = $this->ask('Ім\'я');
            if (empty($name)) {
                $this->error('Ім\'я не може бути порожнім');
                continue;
            }
            if (strlen($name) > 50) {
                $this->error('Ім\'я не може бути довше 50 символів');
                continue;
            }
            break;
        } while (true);

        // Пароль
        do {
            $password = $this->secret('Пароль (мінімум 8 символів)');
            $passwordConfirm = $this->secret('Підтвердження паролю');

            $passwordValidation = $this->validatePassword($password, $passwordConfirm);
            if ($passwordValidation !== true) {
                $this->error($passwordValidation);
                continue;
            }
            break;
        } while (true);

        // Створення
        try {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            $this->displaySuccess("Користувача {$user->name} ({$user->email}) успішно створено!");
        } catch (\Exception $e) {
            $this->displayError('Помилка при створенні користувача: ' . $e->getMessage());
        }
    }

    protected function editUser(): void
    {
        $user = $this->selectUser('Виберіть користувача для редагування');
        if (!$user) {
            return;
        }

        $this->info("Редагування користувача: {$user->name} ({$user->email})");
        $this->info('Залиште поле порожнім, щоб залишити поточне значення');

        // Ім'я
        do {
            $name = $this->ask('Ім\'я', $user->name);
            if (empty($name)) {
                $this->error('Ім\'я не може бути порожнім');
                continue;
            }
            if (strlen($name) > 50) {
                $this->error('Ім\'я не може бути довше 50 символів');
                continue;
            }
            break;
        } while (true);

        // Email
        do {
            $email = $this->ask('Email', $user->email);

            // Валідуємо тільки якщо email змінився
            if ($email !== $user->email) {
                $emailValidation = $this->validateEmail($email);
                if ($emailValidation !== true) {
                    $this->error($emailValidation);
                    continue;
                }
            }
            break;
        } while (true);

        // Перевірка чи щось змінилось
        if ($name === $user->name && $email === $user->email) {
            $this->info('Дані не змінилися');
            return;
        }

        try {
            $user->update([
                'name' => $name,
                'email' => $email,
            ]);

            $this->displaySuccess("Дані користувача успішно оновлено!");
            $this->info("Нові дані: {$user->name} ({$user->email})");
        } catch (\Exception $e) {
            $this->displayError('Помилка при редагуванні користувача: ' . $e->getMessage());
        }
    }

    protected function deleteUser(): void
    {
        $user = $this->selectUser('Виберіть користувача для видалення');
        if (!$user) {
            return;
        }

        $this->warn("Ви збираєтесь видалити користувача: {$user->name} ({$user->email})");

        if (!$this->confirm('Ви впевнені?', false)) {
            $this->info('Видалення скасовано');
            return;
        }

        try {
            // Видалення токенів
            if ($user->accessToken) {
                $user->accessToken->delete();
            }

            // Відв'язка ролей
            $user->roles()->detach();

            // Видалення користувача
            $user->delete();

            $this->displaySuccess("Користувача {$user->name} успішно видалено!");
        } catch (\Exception $e) {
            $this->displayError('Помилка при видаленні користувача: ' . $e->getMessage());
        }
    }

    protected function changeUserPassword(): void
    {
        $user = $this->selectUser('Виберіть користувача для зміни паролю');
        if (!$user) {
            return;
        }

        $this->info("Зміна паролю для користувача: {$user->name} ({$user->email})");

        do {
            $password = $this->secret('Новий пароль (мінімум 8 символів)');
            $passwordConfirm = $this->secret('Підтвердження паролю');

            $passwordValidation = $this->validatePassword($password, $passwordConfirm);
            if ($passwordValidation !== true) {
                $this->error($passwordValidation);
                continue;
            }
            break;
        } while (true);

        try {
            $user->update(['password' => Hash::make($password)]);
            $this->displaySuccess("Пароль для користувача {$user->name} успішно змінено!");
        } catch (\Exception $e) {
            $this->displayError('Помилка при зміні паролю: ' . $e->getMessage());
        }
    }

    protected function assignRoleToUser(): void
    {
        $user = $this->selectUser('Виберіть користувача');
        if (!$user) {
            return;
        }

        // Отримати ролі, які ще не призначені користувачу
        $assignedRoleIds = $user->roles->pluck('id')->toArray();
        $availableRoles = Role::whereNotIn('id', $assignedRoleIds)->get();

        if ($availableRoles->isEmpty()) {
            $this->warn('Всі доступні ролі вже призначені цьому користувачу');
            return;
        }

        $role = $this->selectRole('Виберіть роль для призначення', $availableRoles);
        if (!$role) {
            return;
        }

        try {
            $user->roles()->attach($role->id);
            $this->displaySuccess("Роль '{$role->name}' успішно призначена користувачу {$user->name}!");
        } catch (\Exception $e) {
            $this->displayError('Помилка при призначенні ролі: ' . $e->getMessage());
        }
    }

    protected function removeRoleFromUser(): void
    {
        $user = $this->selectUser('Виберіть користувача');
        if (!$user) {
            return;
        }

        if ($user->roles->isEmpty()) {
            $this->warn('У цього користувача немає ролей');
            return;
        }

        $role = $this->selectRole('Виберіть роль для видалення', $user->roles);
        if (!$role) {
            return;
        }

        try {
            $user->roles()->detach($role->id);
            $this->displaySuccess("Роль '{$role->name}' успішно видалена від користувача {$user->name}!");
        } catch (\Exception $e) {
            $this->displayError('Помилка при видаленні ролі: ' . $e->getMessage());
        }
    }

    // ==================== МЕНЮ РОЛЕЙ ====================

    protected function rolesMenu(): void
    {
        do {
            $choice = $this->numberedChoice(
                'Ролі - оберіть дію',
                [
                    'Відобразити перелік',
                    'Створити роль',
                    'Редагувати роль',
                    'Видалити роль',
                    'Додати дозволи у роль',
                    'Видалити дозволи із ролі',
                    'Назад',
                ]
            );

            $this->newLine();

            switch ($choice) {
                case 'Відобразити перелік':
                    $this->listRoles();
                    break;
                case 'Створити роль':
                    $this->createRole();
                    break;
                case 'Редагувати роль':
                    $this->editRole();
                    break;
                case 'Видалити роль':
                    $this->deleteRole();
                    break;
                case 'Додати дозволи у роль':
                    $this->addAllowensesToRole();
                    break;
                case 'Видалити дозволи із ролі':
                    $this->removeAllowensesFromRole();
                    break;
                case 'Назад':
                    return;
            }

            $this->newLine();
        } while (true);
    }

    protected function listRoles(): void
    {
        $roles = Role::withCount('allowenses')->get();

        if ($roles->isEmpty()) {
            $this->warn('Ролей не знайдено');
            return;
        }

        $data = $roles->map(function ($role) {
            return [
                'ID' => $role->id,
                'Назва' => $role->name,
                'Кількість дозволів' => $role->allowenses_count,
            ];
        })->toArray();

        $this->table(['ID', 'Назва', 'Кількість дозволів'], $data);
    }

    protected function createRole(): void
    {
        $this->info('Створення нової ролі');

        do {
            $name = $this->ask('Назва ролі');
            if (empty($name)) {
                $this->error('Назва не може бути порожньою');
                continue;
            }

            if (Role::where('name', $name)->exists()) {
                $this->error('Роль з такою назвою вже існує');
                continue;
            }

            break;
        } while (true);

        try {
            $role = Role::create(['name' => $name]);
            $this->displaySuccess("Роль '{$role->name}' успішно створена!");
        } catch (\Exception $e) {
            $this->displayError('Помилка при створенні ролі: ' . $e->getMessage());
        }
    }

    protected function editRole(): void
    {
        $role = $this->selectRole('Виберіть роль для редагування');
        if (!$role) {
            return;
        }

        $this->info("Редагування ролі: {$role->name}");

        do {
            $name = $this->ask('Нова назва ролі', $role->name);
            if (empty($name)) {
                $this->error('Назва не може бути порожньою');
                continue;
            }

            if ($name !== $role->name && Role::where('name', $name)->exists()) {
                $this->error('Роль з такою назвою вже існує');
                continue;
            }

            break;
        } while (true);

        try {
            $oldName = $role->name;
            $role->update(['name' => $name]);
            $this->displaySuccess("Роль '{$oldName}' успішно перейменована на '{$name}'!");
        } catch (\Exception $e) {
            $this->displayError('Помилка при редагуванні ролі: ' . $e->getMessage());
        }
    }

    protected function deleteRole(): void
    {
        $role = $this->selectRole('Виберіть роль для видалення');
        if (!$role) {
            return;
        }

        // Перевірка використання
        $usersCount = $role->users()->count();
        if ($usersCount > 0) {
            $this->warn("Увага! Ця роль призначена {$usersCount} користувачу(-ам).");
        }

        $allowensesCount = $role->allowenses()->count();
        if ($allowensesCount > 0) {
            $this->info("Роль має {$allowensesCount} дозвіл(-ів).");
        }

        $this->warn("Ви збираєтесь видалити роль: {$role->name}");

        if (!$this->confirm('Ви впевнені?', false)) {
            $this->info('Видалення скасовано');
            return;
        }

        try {
            // Відв'язка дозволів та користувачів
            $role->allowenses()->detach();
            $role->users()->detach();

            // Видалення ролі
            $role->delete();

            $this->displaySuccess("Роль '{$role->name}' успішно видалена!");
        } catch (\Exception $e) {
            $this->displayError('Помилка при видаленні ролі: ' . $e->getMessage());
        }
    }

    protected function addAllowensesToRole(): void
    {
        $role = $this->selectRole('Виберіть роль');
        if (!$role) {
            return;
        }

        // Отримати дозволи, які ще не в ролі
        $assignedAllowenseIds = $role->allowenses->pluck('id')->toArray();
        $availableAllowenses = Allowense::whereNotIn('id', $assignedAllowenseIds)->get();

        if ($availableAllowenses->isEmpty()) {
            $this->warn('Всі доступні дозволи вже додані до цієї ролі');
            return;
        }

        $this->info("Додавання дозволів до ролі: {$role->name}");
        $this->info('Оберіть дозволи для додавання (можна обрати декілька):');

        $selectedAllowenses = $this->selectMultipleAllowenses($availableAllowenses);

        if (empty($selectedAllowenses)) {
            $this->info('Не обрано жодного дозволу');
            return;
        }

        try {
            $role->allowenses()->attach($selectedAllowenses);
            $count = count($selectedAllowenses);
            $this->displaySuccess("До ролі '{$role->name}' успішно додано {$count} дозвіл(-ів)!");
        } catch (\Exception $e) {
            $this->displayError('Помилка при додаванні дозволів: ' . $e->getMessage());
        }
    }

    protected function removeAllowensesFromRole(): void
    {
        $role = $this->selectRole('Виберіть роль');
        if (!$role) {
            return;
        }

        if ($role->allowenses->isEmpty()) {
            $this->warn('У цієї ролі немає дозволів');
            return;
        }

        $this->info("Видалення дозволів з ролі: {$role->name}");
        $this->info('Оберіть дозволи для видалення (можна обрати декілька):');

        $selectedAllowenses = $this->selectMultipleAllowenses($role->allowenses);

        if (empty($selectedAllowenses)) {
            $this->info('Не обрано жодного дозволу');
            return;
        }

        try {
            $role->allowenses()->detach($selectedAllowenses);
            $count = count($selectedAllowenses);
            $this->displaySuccess("З ролі '{$role->name}' успішно видалено {$count} дозвіл(-ів)!");
        } catch (\Exception $e) {
            $this->displayError('Помилка при видаленні дозволів: ' . $e->getMessage());
        }
    }

    // ==================== МЕНЮ ДОЗВОЛІВ ====================

    protected function allowensesMenu(): void
    {
        do {
            $choice = $this->numberedChoice(
                'Дозволи - оберіть дію',
                [
                    'Відобразити перелік',
                    'Створити дозвіл',
                    'Редагувати дозвіл',
                    'Видалити дозвіл',
                    'Назад',
                ]
            );

            $this->newLine();

            switch ($choice) {
                case 'Відобразити перелік':
                    $this->listAllowenses();
                    break;
                case 'Створити дозвіл':
                    $this->createAllowense();
                    break;
                case 'Редагувати дозвіл':
                    $this->editAllowense();
                    break;
                case 'Видалити дозвіл':
                    $this->deleteAllowense();
                    break;
                case 'Назад':
                    return;
            }

            $this->newLine();
        } while (true);
    }

    protected function listAllowenses(): void
    {
        $allowenses = Allowense::orderBy('section')->orderBy('action')->get();

        if ($allowenses->isEmpty()) {
            $this->warn('Дозволів не знайдено');
            return;
        }

        $data = $allowenses->map(function ($allowense) {
            return [
                'ID' => $allowense->id,
                'Секція' => $allowense->section,
                'Дія' => $allowense->action,
            ];
        })->toArray();

        $this->table(['ID', 'Секція', 'Дія'], $data);
    }

    protected function createAllowense(): void
    {
        $this->info('Створення нового дозволу');

        // Секція
        do {
            $section = $this->ask('Секція (наприклад: items, users, reports)');
            if (empty($section)) {
                $this->error('Секція не може бути порожньою');
                continue;
            }
            if (strlen($section) > 50) {
                $this->error('Секція не може бути довше 50 символів');
                continue;
            }
            break;
        } while (true);

        // Дія
        do {
            $action = $this->ask('Дія (наприклад: create, read, update, delete)');
            if (empty($action)) {
                $this->error('Дія не може бути порожньою');
                continue;
            }

            // Перевірка унікальності
            if (Allowense::where('section', $section)->where('action', $action)->exists()) {
                $this->error("Дозвіл з секцією '{$section}' та дією '{$action}' вже існує");
                continue;
            }

            break;
        } while (true);

        try {
            $allowense = Allowense::create([
                'section' => $section,
                'action' => $action,
            ]);

            $this->displaySuccess("Дозвіл '{$allowense->section}:{$allowense->action}' успішно створено!");
        } catch (\Exception $e) {
            $this->displayError('Помилка при створенні дозволу: ' . $e->getMessage());
        }
    }

    protected function editAllowense(): void
    {
        $allowense = $this->selectAllowense('Виберіть дозвіл для редагування');
        if (!$allowense) {
            return;
        }

        $this->info("Редагування дозволу: {$allowense->section}:{$allowense->action}");

        // Секція
        do {
            $section = $this->ask('Секція', $allowense->section);
            if (empty($section)) {
                $this->error('Секція не може бути порожньою');
                continue;
            }
            if (strlen($section) > 50) {
                $this->error('Секція не може бути довше 50 символів');
                continue;
            }
            break;
        } while (true);

        // Дія
        do {
            $action = $this->ask('Дія', $allowense->action);
            if (empty($action)) {
                $this->error('Дія не може бути порожньою');
                continue;
            }

            // Перевірка унікальності (тільки якщо змінилося)
            if (($section !== $allowense->section || $action !== $allowense->action) &&
                Allowense::where('section', $section)->where('action', $action)->exists()) {
                $this->error("Дозвіл з секцією '{$section}' та дією '{$action}' вже існує");
                continue;
            }

            break;
        } while (true);

        try {
            $allowense->update([
                'section' => $section,
                'action' => $action,
            ]);

            $this->displaySuccess("Дозвіл успішно оновлено на '{$section}:{$action}'!");
        } catch (\Exception $e) {
            $this->displayError('Помилка при редагуванні дозволу: ' . $e->getMessage());
        }
    }

    protected function deleteAllowense(): void
    {
        $allowense = $this->selectAllowense('Виберіть дозвіл для видалення');
        if (!$allowense) {
            return;
        }

        // Перевірка використання в ролях
        $rolesCount = $allowense->roles()->count();
        if ($rolesCount > 0) {
            $this->warn("Увага! Цей дозвіл використовується в {$rolesCount} ролі(-ях):");
            $roles = $allowense->roles()->get();
            foreach ($roles as $role) {
                $this->line("  - {$role->name}");
            }
        }

        $this->warn("Ви збираєтесь видалити дозвіл: {$allowense->section}:{$allowense->action}");

        if (!$this->confirm('Ви впевнені?', false)) {
            $this->info('Видалення скасовано');
            return;
        }

        try {
            // Відв'язка від ролей
            $allowense->roles()->detach();

            // Видалення дозволу
            $allowense->delete();

            $this->displaySuccess("Дозвіл '{$allowense->section}:{$allowense->action}' успішно видалено!");
        } catch (\Exception $e) {
            $this->displayError('Помилка при видаленні дозволу: ' . $e->getMessage());
        }
    }

    // ==================== ХЕЛПЕР МЕТОДИ ====================

    protected function selectUser(string $message = 'Виберіть користувача'): ?User
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->warn('Користувачів не знайдено');
            return null;
        }

        $choices = [];
        $index = 1;
        foreach ($users as $user) {
            $choices[$index] = "{$user->id}: {$user->name} ({$user->email})";
            $index++;
        }

        $choices[$index] = 'Скасувати';

        $selected = $this->choice($message, $choices);

        if ($selected === 'Скасувати') {
            return null;
        }

        // Витягуємо ID з рядка "ID: Name (Email)"
        $id = intval(explode(':', $selected)[0]);

        return $users->find($id);
    }

    protected function selectRole(string $message = 'Виберіть роль', $roles = null): ?Role
    {
        if ($roles === null) {
            $roles = Role::all();
        }

        if ($roles->isEmpty()) {
            $this->warn('Ролей не знайдено');
            return null;
        }

        $choices = [];
        $index = 1;
        foreach ($roles as $role) {
            $choices[$index] = "{$role->id}: {$role->name}";
            $index++;
        }

        $choices[$index] = 'Скасувати';

        $selected = $this->choice($message, $choices);

        if ($selected === 'Скасувати') {
            return null;
        }

        // Витягуємо ID з рядка "ID: Name"
        $id = intval(explode(':', $selected)[0]);

        return $roles->find($id);
    }

    protected function selectAllowense(string $message = 'Виберіть дозвіл'): ?Allowense
    {
        $allowenses = Allowense::orderBy('section')->orderBy('action')->get();

        if ($allowenses->isEmpty()) {
            $this->warn('Дозволів не знайдено');
            return null;
        }

        $choices = [];
        $index = 1;
        foreach ($allowenses as $allowense) {
            $choices[$index] = "{$allowense->id}: {$allowense->section} - {$allowense->action}";
            $index++;
        }

        $choices[$index] = 'Скасувати';

        $selected = $this->choice($message, $choices);

        if ($selected === 'Скасувати') {
            return null;
        }

        // Витягуємо ID з рядка "ID: Section - Action"
        $id = intval(explode(':', $selected)[0]);

        return $allowenses->find($id);
    }

    protected function selectMultipleAllowenses($allowenses): array
    {
        $selected = [];

        do {
            // Показати вже обрані
            if (!empty($selected)) {
                $this->info('Обрані дозволи:');
                foreach ($selected as $id) {
                    $allowense = $allowenses->find($id);
                    if ($allowense) {
                        $this->line("  - {$allowense->section}:{$allowense->action}");
                    }
                }
                $this->newLine();
            }

            // Фільтруємо вже обрані
            $available = $allowenses->whereNotIn('id', $selected);

            if ($available->isEmpty()) {
                $this->info('Всі дозволи обрані');
                break;
            }

            $choices = [];
            $index = 1;
            foreach ($available as $allowense) {
                $choices[$index] = "{$allowense->id}: {$allowense->section} - {$allowense->action}";
                $index++;
            }

            if (!empty($selected)) {
                $choices[$index] = '✓ Завершити вибір';
                $index++;
            }
            $choices[$index] = 'Скасувати';

            $choice = $this->choice('Оберіть дозвіл (по одному)', $choices);

            if ($choice === 'Скасувати') {
                return [];
            }

            if ($choice === '✓ Завершити вибір') {
                break;
            }

            // Витягуємо ID з рядка "ID: Section - Action"
            $id = intval(explode(':', $choice)[0]);
            $selected[] = $id;
        } while (true);

        return $selected;
    }

    protected function validateEmail(string $email): string|bool
    {
        if (empty($email)) {
            return 'Email не може бути порожнім';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Невірний формат email';
        }

        if (strlen($email) > 60) {
            return 'Email не може бути довше 60 символів';
        }

        if (User::where('email', $email)->exists()) {
            return 'Користувач з таким email вже існує';
        }

        return true;
    }

    protected function validatePassword(string $password, string $confirmation): string|bool
    {
        if (empty($password)) {
            return 'Пароль не може бути порожнім';
        }

        if (strlen($password) < 8) {
            return 'Пароль має містити мінімум 8 символів';
        }

        if ($password !== $confirmation) {
            return 'Паролі не співпадають';
        }

        return true;
    }

    protected function displaySuccess(string $message): void
    {
        $this->info("✓ {$message}");
    }

    protected function displayError(string $message): void
    {
        $this->error("✗ {$message}");
    }
}
