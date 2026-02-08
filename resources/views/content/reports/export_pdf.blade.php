<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Export des ventes</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
    h1 { font-size: 18px; margin-bottom: 6px; }
    .meta { margin-bottom: 16px; color: #475569; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; }
    th { background: #f8fafc; }
    .text-end { text-align: right; }
  </style>
</head>
<body>
  <h1>Export des ventes</h1>
  <div class="meta">Période : {{ ucfirst($period) }} | Ventes: {{ $totals['sales'] }} | Montant: {{ number_format($totals['amount'], 0, ',', ' ') }} FCFA</div>

  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Routeur</th>
        <th>Profil</th>
        <th class="text-end">Montant</th>
        <th>Client</th>
      </tr>
    </thead>
    <tbody>
      @foreach($sales as $sale)
        <tr>
          <td>{{ $sale->created_at?->format('d/m/Y H:i') }}</td>
          <td>{{ $sale->router?->name ?? 'Non assigné' }}</td>
          <td>{{ $sale->profile?->name ?? 'Profil inconnu' }}</td>
          <td class="text-end">{{ number_format($sale->total_price, 0, ',', ' ') }} FCFA</td>
          <td>{{ $sale->customer_number }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
