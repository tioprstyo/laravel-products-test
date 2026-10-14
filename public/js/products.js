(() => {
    'use strict';

    const config = window.ProductApp;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    const form = document.getElementById('product-form');
    const submitButton = document.getElementById('submit-button');
    const tbody = document.getElementById('products-body');
    const grandTotalCell = document.getElementById('grand-total');
    const alertArea = document.getElementById('alert-area');

    let products = [];

    const money = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const integer = new Intl.NumberFormat();
    const dateTime = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'medium' });

    async function request(url, method = 'GET', body = null) {
        const response = await fetch(url, {
            method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body ? JSON.stringify(body) : null,
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const error = new Error(data.message || `Request failed (${response.status}).`);
            error.status = response.status;
            error.errors = data.errors || {};
            throw error;
        }

        return data;
    }

    function showAlert(message, type = 'success') {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.setAttribute('role', type === 'danger' ? 'alert' : 'status');
        alert.textContent = message;

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn-close';
        close.setAttribute('aria-label', 'Close');
        close.addEventListener('click', () => alert.remove());
        alert.append(close);

        alertArea.replaceChildren(alert);
        setTimeout(() => alert.remove(), 4000);
    }

    function cell(text, className = '') {
        const td = document.createElement('td');
        td.textContent = text;
        if (className) td.className = className;
        return td;
    }

    function render(data) {
        products = data.products;
        grandTotalCell.textContent = money.format(data.grand_total);

        if (products.length === 0) {
            const tr = document.createElement('tr');
            const td = cell('No products submitted yet.', 'text-center text-muted py-4');
            td.colSpan = 6;
            tr.append(td);
            tbody.replaceChildren(tr);
            return;
        }

        tbody.replaceChildren(...products.map(renderRow));
    }

    function renderRow(product) {
        const tr = document.createElement('tr');
        tr.dataset.id = product.id;

        const actions = document.createElement('td');
        actions.className = 'text-end';
        const edit = document.createElement('button');
        edit.type = 'button';
        edit.className = 'btn btn-sm btn-outline-secondary';
        edit.textContent = 'Edit';
        edit.setAttribute('aria-label', `Edit ${product.product_name}`);
        edit.addEventListener('click', () => tr.replaceWith(renderEditRow(product)));
        actions.append(edit);

        tr.append(
            cell(product.product_name),
            cell(integer.format(product.quantity), 'text-end'),
            cell(money.format(product.price), 'text-end'),
            cell(dateTime.format(new Date(product.submitted_at)), 'text-nowrap-md'),
            cell(money.format(product.total_value), 'text-end'),
            actions,
        );

        return tr;
    }

    function input(name, value, label, inputMode = 'text') {
        const field = document.createElement('input');
        field.type = 'text';
        field.name = name;
        field.value = value;
        field.inputMode = inputMode;
        field.className = 'form-control form-control-sm';
        field.setAttribute('aria-label', label);

        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.dataset.errorFor = name;

        const td = document.createElement('td');
        td.append(field, feedback);
        return td;
    }

    function renderEditRow(product) {
        const tr = document.createElement('tr');
        tr.dataset.id = product.id;
        tr.className = 'table-warning';

        const actions = document.createElement('td');
        actions.className = 'text-end text-nowrap';

        const save = document.createElement('button');
        save.type = 'button';
        save.className = 'btn btn-sm btn-success me-1';
        save.textContent = 'Save';

        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'btn btn-sm btn-outline-secondary';
        cancel.textContent = 'Cancel';
        cancel.addEventListener('click', () => tr.replaceWith(renderRow(product)));

        actions.append(save, cancel);

        tr.append(
            input('product_name', product.product_name, 'Product name'),
            input('quantity', product.quantity, 'Quantity in stock', 'numeric'),
            input('price', product.price, 'Price per item', 'decimal'),
            cell(dateTime.format(new Date(product.submitted_at)), 'text-nowrap-md'),
            cell(money.format(product.total_value), 'text-end'),
            actions,
        );

        const submitEdit = async () => {
            const values = Object.fromEntries(
                [...tr.querySelectorAll('input')].map((field) => [field.name, field.value.trim()]),
            );

            clearErrors(tr);
            save.disabled = true;

            try {
                const data = await request(config.updateUrl + encodeURIComponent(product.id), 'PUT', values);
                render(data);
                showAlert(data.message);
            } catch (error) {
                handleError(error, tr);
            } finally {
                save.disabled = false;
            }
        };

        save.addEventListener('click', submitEdit);
        tr.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                submitEdit();
            } else if (event.key === 'Escape') {
                tr.replaceWith(renderRow(product));
            }
        });

        setTimeout(() => tr.querySelector('input')?.focus());

        return tr;
    }

    function clearErrors(scope) {
        scope.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        scope.querySelectorAll('[data-error-for]').forEach((el) => { el.textContent = ''; });
    }

    function handleError(error, scope) {
        if (error.status === 422) {
            for (const [name, messages] of Object.entries(error.errors)) {
                scope.querySelector(`[name="${name}"]`)?.classList.add('is-invalid');
                const feedback = scope.querySelector(`[data-error-for="${name}"]`);
                if (feedback) feedback.textContent = messages[0];
            }
            scope.querySelector('.is-invalid')?.focus();
            return;
        }

        showAlert(error.status === 419 ? 'Your session expired. Please reload the page.' : error.message, 'danger');
    }

    function setLoading(isLoading) {
        submitButton.disabled = isLoading;
        submitButton.querySelector('.spinner-border').classList.toggle('d-none', !isLoading);
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(form);
        setLoading(true);

        const values = Object.fromEntries(
            ['product_name', 'quantity', 'price'].map((name) => [name, form.elements[name].value.trim()]),
        );

        try {
            const data = await request(config.storeUrl, 'POST', values);
            render(data);
            form.reset();
            form.elements.product_name.focus();
            showAlert(data.message);
        } catch (error) {
            handleError(error, form);
        } finally {
            setLoading(false);
        }
    });

    request(config.listUrl)
        .then(render)
        .catch((error) => showAlert(error.message, 'danger'));
})();
