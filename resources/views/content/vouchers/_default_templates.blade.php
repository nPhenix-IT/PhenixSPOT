<style>
    .voucher-card {
        display: inline-block;
        border: 1px solid #ccc;
        margin: 5px;
        padding: 10px;
        width: 250px;
        font-family: sans-serif;
        font-size: 12px;
    }
    .voucher-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
        margin-bottom: 5px;
    }
    .voucher-header .price {
        font-size: 18px;
        font-weight: bold;
        color: #007bff;
    }
    .voucher-body {
        text-align: center;
    }
    .voucher-body .code {
        font-size: 20px;
        font-weight: bold;
        letter-spacing: 2px;
        margin: 10px 0;
        padding: 5px;
        border: 1px dashed #000;
        background-color: #f8f9fa;
    }
    .voucher-footer {
        margin-top: 10px;
        font-size: 10px;
        color: #6c757d;
    }
</style>

<div class="voucher-card">
    <div class="voucher-header">
        <span>@{{ profile_name }}</span>
        <span class="price">@{{ price }}</span>
    </div>
    <div class="voucher-body">
        <div>Code d'accès</div>
        <div class="code">@{{ code }}</div>
    </div>
    <div class="qrcode-container">
            @{{ qrcode }}
        </div>
    <div class="voucher-footer">
        <div><strong>Validité:</strong> @{{ validity }}</div>
        <div><strong>Limite de données:</strong> @{{ data_limit }}</div>
    </div>
</div>
