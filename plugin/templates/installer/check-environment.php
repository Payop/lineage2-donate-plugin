<?php include '_header.php'; ?>

<div>
    <h4 class="text-center mb-3 mt-3">Проверка окружения</h4>

    <div class="offset-1 col-md-11">
        <p class="<?= $php['testPassed'] ? 'text-success' : 'text-error' ?>">
            <strong>PHP Version:</strong> <?= $php['version'] ?>. Required: 7.1
        </p>
        <p class="<?= $mysql['testPassed'] ? 'text-success' : 'text-error' ?>">
            <strong>PDO & Mysql:</strong> <?= $mysql['testPassed'] ? 'Драйвер найден'
                : '<a href="http://php.net/manual/en/ref.pdo-mysql.php" target="_blank">Установите PDO mysql драйвер</a>' ?>
        </p>
        <p class="<?= $config['testPassed'] ? 'text-success' : 'text-error' ?>">
            <strong>Configuration file:</strong> <?= $config['testPassed']
                ? 'Файл config.json доступен для записи'
                : 'Проверьте, что файл config.json существует и обладает правами на запись 666 (для LINUX/UNIX систем)' ?></p>
    </div>
</div>
<?php if ($php['testPassed'] && $mysql['testPassed'] && $config['testPassed']): ?>
<div>
    <a class="btn btn-primary btn-large btn-block" href="/installer.php?step=configuration" title="Продолжить установку">
        Продолжить установку
    </a>
</div>
<?php endif; ?>


<?php include '_footer.php'; ?>