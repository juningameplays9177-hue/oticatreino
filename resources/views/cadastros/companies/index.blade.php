@php use Illuminate\Support\Facades\Storage; @endphp
<x-app-layout title="Minhas Lojas">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-900 mb-4 sm:mb-0">Minhas Lojas</h1>
            <a href="{{ route('cadastros.companies.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nova Loja
            </a>
        </div>

        @if(isset($hasLicenseIssues) && $hasLicenseIssues)
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-800 font-medium">⚠️ Atenção: Pelo menos uma loja está com problemas de licenciamento.</p>
                @if(isset($licenseIssues))
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                        @foreach($licenseIssues as $issue)
                            <li>{{ $issue['company'] }} - Licença: {{ $issue['license_status'] }}, Certificado: {{ $issue['cert_status'] }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">{{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                <p class="text-sm text-slate-600">
                    @if($companies->count() > 0)
                        Foram encontradas <strong>{{ $companies->total() }}</strong> lojas.
                        @if($companies->hasPages())
                            Mostrando página <strong>{{ $companies->currentPage() }}</strong> de <strong>{{ $companies->lastPage() }}</strong>.
                        @endif
                    @else
                        Nenhuma loja cadastrada.
                    @endif
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Logo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Razão Social / Nome Fantasia</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Identificação</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse($companies as $company)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    @if($company->logo_path)
                                        <img src="{{ Storage::url($company->logo_path) }}" alt="Logo" class="w-10 h-10 rounded">
                                    @else
                                        <div class="w-10 h-10 rounded bg-slate-200 flex items-center justify-center">
                                            <span class="text-xs text-slate-500">N/A</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $company->trade_name }}</div>
                                    <div class="text-sm text-slate-500">{{ $company->legal_name }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $company->slug ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $company->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $company->is_active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('cadastros.companies.edit', $company->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">Editar</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">Nenhuma loja encontrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($companies->hasPages())
                <div class="px-4 py-3 bg-slate-50 border-t border-slate-200">{{ $companies->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>

