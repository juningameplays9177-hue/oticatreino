@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">🧹 Limpeza de Lentes Duplicadas</h1>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            @if(isset($results))
                <div class="mb-6">
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600">Total de Lentes</div>
                            <div class="text-2xl font-bold text-blue-600">{{ $results['total_lenses'] }}</div>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600">Código Antigo</div>
                            <div class="text-2xl font-bold text-red-600">{{ $results['old_format_count'] }}</div>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600">Código Sequencial</div>
                            <div class="text-2xl font-bold text-green-600">{{ $results['new_format_count'] }}</div>
                        </div>
                    </div>

                    @if($results['old_format_count'] > 0)
                        <div class="mb-4">
                            <h2 class="text-lg font-semibold text-gray-700 mb-2">Lentes que serão removidas:</h2>
                            <div class="bg-gray-50 rounded-lg p-4 max-h-64 overflow-y-auto">
                                <ul class="space-y-1">
                                    @foreach($results['old_format_lenses'] as $lens)
                                        <li class="text-sm text-gray-700">
                                            <span class="font-mono text-red-600">{{ $lens['ref'] }}</span> - {{ $lens['name'] }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('clean.lenses.confirm') }}" onsubmit="return confirm('⚠️ ATENÇÃO: Isso irá deletar {{ $results['old_format_count'] }} lentes com código antigo!\\n\\nTem certeza que deseja continuar?');">
                            @csrf
                            <button type="submit" class="w-full bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 font-semibold">
                                🗑️ Remover {{ $results['old_format_count'] }} Lentes Duplicadas
                            </button>
                        </form>
                    @else
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                            ✅ Nenhuma lente duplicada encontrada! Todas as lentes já estão com código sequencial.
                        </div>
                    @endif
                </div>
            @else
                <form method="GET" action="{{ route('clean.lenses') }}">
                    <button type="submit" class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold">
                        🔍 Verificar Lentes Duplicadas
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection

