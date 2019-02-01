<!DOCTYPE html>
<html>
<head>
    <title>PayOp – accept online payments!</title>
    <meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
    <link rel="stylesheet" type="text/css" media="screen" href="/css/bootstrap.min.css">
</head>
<body>

<div class="container">
    <h2 class="pb-2">Форма Доната</h2>
    <div class="alert alert-danger invisible" id="formError" role="alert"></div>
    <form id="paymentForm" class="pt-2 pb-3">
        <div class="row mb-3">
            <label class="col-form-label col-md-2" for="account">Ник персонажа:</label>
            <div class="col-md-5">
                <input id="account" class="form-control" type="text" name="accountName" value="">
                <div class="invalid-feedback" id="accountError"></div>
            </div>
        </div>
        <div class="row mb-3">
            <label class="col-form-label col-md-2">Тип монет:</label>
            <div class="col-md-5">
                <input class="form-control" type="text" readonly disabled value="Coin of Luck">
            </div>
        </div>

        <div class="row mb-3">
            <label class="col-form-label col-md-2" for="coins">Количество:</label>
            <div class="col-md-5">
                <input id="itemsQty" class="form-control" type="text" name="itemsQty" value="<?= $minItemsQty ?>">
                <div class="invalid-feedback" id="itemsQtyError"></div>
                <!--                <small class="help-block text-muted">Минимальное количество: -->
                <? //= $minItemsQty ?><!--</small>-->
            </div>
        </div>

        <div class="row mb-3">
            <label class="col-form-label col-md-2" for="sum">Это будет стоить:</label>
            <div class="col-md-5 d-table">
                <div class="d-table-cell pr-3">
                    <input id="sum" class="form-control"
                           value="<?= $minItemsQty * $itemPrice ?>"
                           type="text" readonly disabled>
                </div>
                <div class="d-table-cell align-middle"><?= $currency ?></div>
            </div>
        </div>

        <div class="row mb-3">
            <input type="hidden" name="description" value="Добровольные пожертвования проекту">
            <input class="btn" type="submit" value="Оплатить">
        </div>
    </form>
</div>

<script>
    const minItemsQty = parseInt('<?=$minItemsQty ?>', 10);
    const itemPrice = parseFloat('<?=$itemPrice ?>');

    const accEl = document.getElementById('account');
    const accErrEl = document.getElementById('accountError');
    const sumEl = document.getElementById('sum');
    const qtyEl = document.getElementById('itemsQty');
    const qtyErrEl = document.getElementById('itemsQtyError');
    const formErrEl = document.getElementById('formError');

    accEl.addEventListener('keyup', e => validateAccount());

    qtyEl.addEventListener('keyup', e => {
        const isValid = validateQty();

        if (isValid) {
            let qty = parseFloat(e.target.value);
            sumEl.value = (qty * itemPrice).toFixed(2)
        }
    });

    document.getElementById('paymentForm').addEventListener('submit', e => {
        e.preventDefault();

        if (isFormValid()) {
            formErrEl.classList.add('invisible');
            formErrEl.textContent = '';

            const data = new FormData();
            data.append('account', accEl.value);
            data.append('qty', parseFloat(qtyEl.value));
            fetch('./index.php?action=payment', {method: 'POST', body: data,})
                .then(response => response.json())
                .then(response => {
                    if ({}.hasOwnProperty.call(response.error, 'message')) {
                        throw response.error.message;
                    }
                    // redirect to payment page
                    window.location.href = response.data.redirectUrl;

                    return false;
                })
                .catch(error => {
                    formErrEl.classList.remove('invisible');
                    formErrEl.textContent = error;
                });
        }

        return false;
    });

    /**
     * @returns {boolean}
     */
    const validateQty = () => {
        let qty = parseFloat(qtyEl.value);
        if (!qty) {
            qtyEl.classList.add('is-invalid');
            qtyErrEl.textContent = 'Колчество монет указано неверно.';
            sumEl.value = 0;
            return false;
        }

        if (qty < minItemsQty) {
            qtyEl.classList.add('is-invalid');
            qtyErrEl.textContent = `Минимальное количество ${minItemsQty} монет.`;
            sumEl.value = 0;
            return false;
        }

        qtyEl.classList.remove('is-invalid');
        qtyErrEl.textContent = '';

        return true;
    };

    /**
     * @returns {boolean}
     */
    const validateAccount = () => {
        if (!accEl.value.length) {
            accEl.classList.add('is-invalid');
            accErrEl.textContent = 'Укажите ник персонажа.';
            return false;
        }

        accEl.classList.remove('is-invalid');
        accErrEl.textContent = '';

        return true;
    };

    /**
     * @returns {boolean}
     */
    const isFormValid = () => validateAccount() && validateQty();

</script>

</body>
</html>