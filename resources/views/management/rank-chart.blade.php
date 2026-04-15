@php
    $layout = request()->boolean('modal') ? 'modal-layout' : 'app-layout';
@endphp
<x-dynamic-component :component="$layout">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="{{ request()->boolean('modal') ? 'max-w-3xl' : 'max-w-7xl' }} mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="h-[420px]">
                        <canvas id="rankBarChart"></canvas>
                    </div>

                    <div class="mt-6">
                        <a href="{{ route('management.index') }}" class="text-indigo-600 font-semibold hover:underline">Back to Management</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('rankBarChart')?.getContext('2d');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($labels),
                datasets: [{
                    label: 'Personnel',
                    data: @json($values),
                    backgroundColor: '#3b82f6',
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: { ticks: { autoSkip: false } },
                    y: { beginAtZero: true }
                }
            }
        });
    }
</script>
