<?php include '_header.php'; ?>

    <div>
        <h4 class="text-center mb-3 mt-3">Проверка сединения с БД и создание таблиц</h4>
        <?php if ($errors): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-success">Таблицы Lineage2 успешно найдены, можно переходить к следующему шагу</div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <a class="btn btn-primary btn-large" href="/installer.php?step=configuration"
                   title="К настройкам">
                    К настройкам
                </a>
            </div>
            <div class="offset-1 col-md-7">
                <?php if (!$errors): ?>
                <a class="btn btn-primary btn-large btn-block" href="installer.php?step=finish" title="Закончить установку">
                    Закончить установку
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php include '_footer.php'; ?>