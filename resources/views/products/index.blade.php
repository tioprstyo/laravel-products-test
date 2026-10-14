<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
    <link href="{{ asset('css/products.css') }}" rel="stylesheet">
</head>
<body class="bg-body-tertiary">
    <main class="container py-4 py-md-5">
        <h1 class="h3 mb-4">Product Inventory</h1>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5 card-title mb-3">Add product</h2>

                <form id="product-form" action="{{ route('products.store') }}" method="POST" novalidate>
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label for="product_name" class="form-label">Product name</label>
                            <input type="text" class="form-control" id="product_name" name="product_name" maxlength="255" required>
                            <div class="invalid-feedback" data-error-for="product_name"></div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label for="quantity" class="form-label">Quantity in stock</label>
                            <input type="text" inputmode="numeric" class="form-control" id="quantity" name="quantity" required>
                            <div class="invalid-feedback" data-error-for="quantity"></div>
                        </div>
                        <div class="col-sm-6 col-md-2">
                            <label for="price" class="form-label">Price per item</label>
                            <input type="text" inputmode="decimal" class="form-control" id="price" name="price" required>
                            <div class="invalid-feedback" data-error-for="price"></div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100" id="submit-button">
                                <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                                <span>Submit</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div id="alert-area" aria-live="polite"></div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 card-title mb-3">Submitted products</h2>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0" id="products-table">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Product name</th>
                                <th scope="col" class="text-end">Quantity in stock</th>
                                <th scope="col" class="text-end">Price per item</th>
                                <th scope="col">Datetime submitted</th>
                                <th scope="col" class="text-end">Total value number</th>
                                <th scope="col" class="text-end"><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody id="products-body">
                            <tr><td colspan="6" class="text-center text-muted py-4">Loading…</td></tr>
                        </tbody>
                        <tfoot>
                            <tr class="table-secondary fw-bold">
                                <th scope="row" colspan="4" class="text-end">Sum total</th>
                                <td class="text-end" id="grand-total">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        window.ProductApp = {
            listUrl: @json(route('products.list')),
            storeUrl: @json(route('products.store')),
            updateUrl: @json(url('products')) + '/',
        };
    </script>
    <script src="{{ asset('js/products.js') }}" defer></script>
</body>
</html>
