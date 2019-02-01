<?php include '_header.php'; ?>

<div>
    <h4 class="text-center mb-3 mt-3">Плагин успешно настроен</h4>

    <div class="alert alert-info">
        <strong>Для завершения процесса подключения выпоните следующие ниже инструкции:</strong>
        <ul>
            <li class="pb-3 pt-3">
                Пропишие в настройках
                Вашего проекта на <a target="_blank" href="https://payop.com/en/profile/projects/">Payop.com</a>
                WEB-адрес обработчика уведомлений (Сallback/IPN url):
                <br>
                <span class="badge badge-success">IPN URL</span> <span class="text-success font-weight-bold"><?= $ipnUrl ?></span>
            </li>
            <li>
                <span class="badge badge-danger">Важно!</span> Не забудьте удалить файл installer.php,
                т.к. его наличие может нести угрозу безопасности плагина и сервера.
            </li>
        </ul>
    </div>
    <small class="form-text text-muted">
        <span class="badge badge-info">INFO</span>
        Все настройки сохранены в файле config.json (данные хранятся в формате JSON),
        вы всегда можете их отредактировать в случае необходимости.
    </small>
</div>

<?php include '_footer.php'; ?>