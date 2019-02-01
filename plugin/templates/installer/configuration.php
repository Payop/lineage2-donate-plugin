<?php include '_header.php'; ?>

<div>
    <h4 class="text-center mb-3 mt-3">Конфигурация плагина</h4>

    <?php if ($saved): ?>
        <div class="alert alert-success" role="alert">Настройки успешно сохранены</div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group row">
            <input type="checkbox" <?= $parameters['enableLogs'] ? 'checked' : '' ?>
                   class="form-check-input"
                   name="enableLogs" id="enableLogs">
            <label class="form-check-label" for="enableLogs">Включить логирование запросов</label>
        </div>

        <div class="row"><h5>Конфигурация Payop проекта</h5></div>

        <div class="form-group row">
            <label class="col-form-label col-md-4" for="publicKey">Project public key</label>
            <div class="col-md-8">
                <input type="text" value="<?= $parameters['publicKey'] ?>"
                       class="form-control"
                       required="required" name="publicKey" id="publicKey">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-form-label col-md-4" for="secretKey">Project secret key</label>
            <div class="col-md-8">
                <input type="text" value="<?= $parameters['secretKey'] ?>"
                       class="form-control"
                       required="required" name="secretKey" id="secretKey">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-form-label col-md-4" for="failUrl">Fail page URL</label>
            <div class="col-md-8">
                <input type="text" value="<?= $parameters['failUrl'] ?>"
                       class="form-control"
                       required="required" name="failUrl" id="failUrl">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-form-label col-md-4" for="resultUrl">Success page URL</label>
            <div class="col-md-8">
                <input type="text" value="<?= $parameters['resultUrl'] ?>"
                       class="form-control"
                       required="required" name="resultUrl" id="resultUrl">
            </div>
        </div>

        <div class="row"><h5>Конфигурация товара и стоимости</h5></div>
        <div class="form-group row">
            <label class="col-form-label col-md-4" for="currency">Валюта платежа</label>
            <div class="col-md-8">
                <input type="text" value="<?= $parameters['currency'] ?>"
                       class="form-control"
                       required="required" name="currency" id="currency">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-form-label col-md-4" for="itemPrice">Стоимость единицы товара в указанной выше валюте</label>
            <div class="col-md-8">
                <input type="text" value="<?= $parameters['itemPrice'] ?>"
                       class="form-control"
                       required="required" name="itemPrice" id="itemPrice">
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-8">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-form-label col-md-4" for="minItemsQty">Минимальной количество товара для покупки</label>
            <div class="col-md-8">
                <input type="text" value="<?= $parameters['minItemsQty'] ?>"
                       class="form-control"
                       required="required" name="minItemsQty" id="minItemsQty">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-form-label col-md-4" for="itemId">ID товара за который берется оплата</label>
            <div class="col-md-8">
                <input type="text" value="<?= $parameters['itemId'] ?>"
                       class="form-control"
                       required="required" name="itemId" id="itemId">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-form-label col-md-4" for="itemTable">Название "Таблицы вещей" в базе даных</label>
            <div class="col-md-8">
                <input type="text" value="<?= $parameters['itemTable'] ?>"
                       class="form-control"
                       required="required" name="itemTable" id="itemTable">
            </div>
        </div>

        <div class="row"><h5>Конфигурация БД</h5></div>
        <small class="form-text text-muted">
            Укажите актуальные параметры для соединения с БД, где расположены таблицы Lineage2 (не WEB-движка, а именно самой игры).
        </small>
        <div class="form-group row">
            <label class="col-form-label col-md-4" for="dbHost">Имя хоста</label>
            <div class="col-md-8">
                <input type="text" value="<?= $parameters['dbHost'] ?>"
                       class="form-control"
                       required="required" name="dbHost" id="dbHost">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-form-label col-md-4" for="dbName">Имя БД</label>
            <div class="col-md-8">
                <input type="text" value="<?= $parameters['dbName'] ?>"
                       class="form-control"
                       required="required" name="dbName" id="dbName">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-form-label col-md-4" for="dbUser">Имя пользователя</label>
            <div class="col-md-8">
                <input type="text" value="<?= $parameters['dbUser'] ?>"
                       class="form-control"
                       required="required" name="dbUser" id="dbUser">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-form-label col-md-4" for="dbPass">Пароль пользователя</label>
            <div class="col-md-8">
                <input type="text" value="<?= $parameters['dbPass'] ?>"
                       class="form-control"
                       required="required" name="dbPass" id="dbPass">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-form-label col-md-4" for="dbPort">Номер порта</label>
            <div class="col-md-8">
                <input type="text" value="<?= $parameters['dbPort'] ?>"
                       class="form-control" name="dbPort" id="dbPort">
            </div>
        </div>
        <div class="row">
            <div class="col-md-5">
                <input type="submit" class="btn btn-primary btn-large" value="Сохранить конфигурацию">
            </div>
            <div class="offset-2 col-md-5">
                <a class="btn btn-primary btn-large btn-block" href="/installer.php?step=mysqlConnection" title="Продолжить установку">
                    Продолжить установку
                </a>
            </div>
        </div>
    </form>
</div>

<?php include '_footer.php'; ?>