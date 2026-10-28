
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen | DQAMA</title>
    <?php echo app('Illuminate\Foundation\Vite')('resources/css/app.css'); ?>
</head>
<body>
    
    <main class="mx-auto mt-20 max-w-sm rounded bg-white p-6 shadow">
        <h1 class="mb-4 text-2xl font-bold">Inloggen</h1>

        <form method="POST" action="/login" class="space-y-4">
            
            <?php echo csrf_field(); ?>
            <div>
                <label for="email">E-mailadres</label>
                <input id="email" name="email" type="email" required class="w-full rounded border p-2">
                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label for="password">Wachtwoord</label>
                
                <input id="password" name="password" type="password" required class="w-full rounded border p-2">
            </div>
            <button class="w-full rounded bg-indigo-600 p-2 text-white">Inloggen</button>
        </form>
    </main>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\the_better_DQAMA\dashboard\resources\views/home.blade.php ENDPATH**/ ?>