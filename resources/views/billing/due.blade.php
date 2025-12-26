@extends('layouts.app')

@section('content')
<div class="py-6">
  <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white shadow sm:rounded-lg p-6">
      <h2 class="text-lg font-semibold mb-4">Student Due Preview</h2>
      <form id="due-form" class="space-y-4" onsubmit="event.preventDefault(); fetchDue();">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <input class="border rounded p-2" type="number" id="student_id" placeholder="Student ID" required />
          <input class="border rounded p-2" type="number" id="class_id" placeholder="Class ID" required />
          <input class="border rounded p-2" type="text" id="month" placeholder="YYYY-MM" value="{{ now()->format('Y-m') }}" required />
        </div>
        <button class="px-4 py-2 bg-indigo-600 text-white rounded">Preview Due</button>
      </form>
      <div id="due-result" class="mt-6"></div>
    </div>
  </div>
</div>

<script>
async function fetchDue() {
  const student = document.getElementById('student_id').value;
  const classId = document.getElementById('class_id').value;
  const month = document.getElementById('month').value;
  const res = await fetch(`/api/v1/billing/students/${student}/due?class_id=${classId}&month=${month}`, { headers: { 'Accept': 'application/json' }});
  const data = await res.json();
  document.getElementById('due-result').innerHTML = `
    <div>
      <p><strong>Month:</strong> ${data.month}</p>
      <p><strong>Total Due:</strong> ${data.total_due ?? (data.lines?.reduce((t,l)=>t+l.net,0) || 0)}</p>
      <table class="min-w-full mt-4 text-sm">
        <thead><tr><th class="text-left">Category</th><th class="text-right">Gross</th><th class="text-right">Discount</th><th class="text-right">Net</th></tr></thead>
        <tbody>
          ${(data.lines||[]).map(l=>`<tr><td>${l.fee_category_id}</td><td class='text-right'>${l.gross}</td><td class='text-right'>${l.discount}</td><td class='text-right'>${l.net}</td></tr>`).join('')}
        </tbody>
      </table>
    </div>`;
}
</script>
@endsection
