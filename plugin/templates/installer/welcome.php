<?php include '_header.php'; ?>

<div>
    <h4 class="text-center mb-3 mt-3">Менджер установки платежей для LINEAGE2</h4>

    <div class="col-md-12">
        <div class="alert alert-info">
            <strong>Пожалуйста, перед началом установки убедитесь в том, что:</strong>
            <ul>
                <li>Скрипт расположен на сервере, имеющем доступ к актуальной рабочей версии БД Lineage2.</li>
                <li>Вы зарегистрировались и создали проект в <a target="_blank" href="https://payop.com">PayOp.com</a>.</li>
            </ul>

        </div>
    </div>
</div>
<div>
    <a class="btn btn-primary btn-block" href="/installer.php?step=checkEnvironment" title="Начать установку">
        Начать установку
    </a>
</div>

<?php include '_footer.php'; ?>