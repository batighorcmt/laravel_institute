@extends('layouts.app')

@section('content')
<div class="py-6">
  <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white shadow sm:rounded-lg p-6">
      <h2 class="text-lg font-semibold mb-4">Collect Payment (Partial/Full)</h2>
      <form id="collect-form" class="space-y-4" onsubmit="event.preventDefault(); collectPayment();">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <input class="border rounded p-2" type="number" id="student_id" placeholder="Student ID" required />
          <input class="border rounded p-2" type="number" id="fee_category_id" placeholder="Fee Category ID" required />
          <input class="border rounded p-2" type="number" step="0.01" id="amount_paid" placeholder="Amount" required />
          <select class="border rounded p-2" id="payment_method">
            <option value="cash">Cash</option>
            <option value="bkash">bKash</option>
            <option value="nagad">Nagad</option>
            <option value="bank">Bank</option>
          </select>
          <input class="border rounded p-2" type="number" step="0.01" id="discount_applied" placeholder="Discount (optional)" />
          <input class="border rounded p-2" type="number" step="0.01" id="fine_applied" placeholder="Fine (optional)" />
        </div>
        <button class="px-4 py-2 bg-indigo-600 text-white rounded">Collect</button>
      </form>

      <div id="collect-result" class="mt-6"></div>
    </div>
  </div>
</div>

<script>
async function collectPayment() {
  const payload = {
    student_id: Number(document.getElementById('student_id').value),
    fee_category_id: Number(document.getElementById('fee_category_id').value),
    amount_paid: Number(document.getElementById('amount_paid').value),
    payment_method: document.getElementById('payment_method').value,
    role: 'teacher',
  };
  const discount = document.getElementById('discount_applied').value;
  const fine = document.getElementById('fine_applied').value;
  if (discount) payload.discount_applied = Number(discount);
  if (fine) payload.fine_applied = Number(fine);

  const res = await fetch('/api/v1/billing/payments', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify(payload)
  });
  const data = await res.json();
  const p = data.payment || {};
  const receipt = p.receipt_id ? `Receipt ID: ${p.receipt_id}` : '(Pending - no receipt for non-cash)';
  document.getElementById('collect-result').innerHTML = `
    <div class="p-4 border rounded">
      <p><strong>Payment ID:</strong> ${p.id ?? '-'}</p>
      <p><strong>Status:</strong> ${p.status ?? '-'}</p>
      <p><strong>Amount:</strong> ${p.amount_paid ?? '-'}</p>
      <p><strong>${receipt}</strong></p>
    </div>`;
}
</script>
@endsection
