@if (session('success'))
    <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-800" role="alert">
        {{ session('success') }}
    </div>
@endif
@if (session('info'))
    <div class="mb-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-800" role="alert">
        {{ session('info') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-800" role="alert">
        {{ session('error') }}
    </div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-800" role="alert">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
