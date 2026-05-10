<x-app-layout title="Dashboard">
    @push('styles')
    <style type="text/css">
        /* Dashboard CSS v2.0 - 2026-01-25 */
        /* ============================================
           DESIGN TOKENS - SaaS Moderno B2B
           ============================================ */
        :root {
            --bg: #F6F7FB;
            --surface: #FFFFFF;
            --border: rgba(15, 23, 42, 0.08);
            
            --text: #0F172A;
            --muted: #64748B;
            
            --primary: #2563EB;
            --primary-2: #7C3AED;
            
            --success: #16A34A;
            --warning: #F59E0B;
            --danger: #EF4444;
            
            --radius: 16px;
            --radius-sm: 12px;
            
            --shadow: 0 10px 30px rgba(2, 6, 23, 0.08);
            --shadow-sm: 0 6px 18px rgba(2, 6, 23, 0.06);
        }
        
        /* Aplicar background no main content area */
        main {
            background: var(--bg) !important;
        }
        
        main > div {
            background: var(--bg) !important;
        }
        
        /* ============================================
           COMPONENTES PADRÃO
           ============================================ */
        .dashboard-container {
            max-width: 1280px !important;
            margin: 0 auto !important;
            padding: 24px !important;
            background: var(--bg) !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
        }
        
        .dashboard-section {
            margin-bottom: 24px;
        }
        
        .card {
            background: var(--surface) !important;
            border: 1px solid var(--border) !important;
            border-radius: var(--radius) !important;
            box-shadow: var(--shadow-sm) !important;
            padding: 16px !important;
            transition: all 0.2s ease !important;
        }
        
        .card:hover {
            box-shadow: var(--shadow) !important;
        }
        
        .kpi-card {
            background: var(--surface) !important;
            border: 1px solid var(--border) !important;
            border-radius: var(--radius) !important;
            box-shadow: var(--shadow-sm) !important;
            padding: 16px !important;
            height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            position: relative !important;
        }
        
        .kpi-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        
        .kpi-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .kpi-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--muted);
        }
        
        .kpi-value {
            font-size: 28px !important;
            font-weight: 700 !important;
            color: var(--text) !important;
            line-height: 1.2 !important;
            margin-bottom: 8px !important;
        }
        
        .kpi-detail {
            font-size: 13px !important;
            color: var(--muted) !important;
            font-weight: 500 !important;
        }
        
        .accent-strip {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            height: 4px !important;
            border-radius: var(--radius) var(--radius) 0 0 !important;
            z-index: 1 !important;
        }
        
        .accent-strip.primary { background: var(--primary) !important; }
        .accent-strip.success { background: var(--success) !important; }
        .accent-strip.warning { background: var(--warning) !important; }
        .accent-strip.danger { background: var(--danger) !important; }
        .accent-strip.primary-2 { background: var(--primary-2) !important; }
        
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: rgba(22, 163, 74, 0.1);
            color: var(--success);
        }
        
        .badge-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }
        
        .badge-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        .badge-muted {
            background: rgba(100, 116, 139, 0.1);
            color: var(--muted);
        }
        
        /* ============================================
           TOPBAR
           ============================================ */
        .topbar {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            margin-bottom: 24px !important;
            padding: 0 !important;
        }
        
        .topbar-left h1 {
            font-size: 24px !important;
            font-weight: 700 !important;
            color: var(--text) !important;
            margin: 0 0 4px 0 !important;
        }
        
        .topbar-left p {
            font-size: 14px !important;
            color: var(--muted) !important;
            font-weight: 500 !important;
            margin: 0 !important;
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .topbar-date {
            text-align: right;
        }
        
        .topbar-date-day {
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
        }
        
        .topbar-date-time {
            font-size: 18px;
            font-weight: 700;
            color: var(--text);
        }
        
        /* ============================================
           CONTEXT BAR
           ============================================ */
        .context-bar {
            background: var(--surface) !important;
            border: 1px solid var(--border) !important;
            border-radius: var(--radius-sm) !important;
            box-shadow: var(--shadow-sm) !important;
            padding: 16px 20px !important;
            margin-bottom: 24px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            flex-wrap: wrap !important;
            gap: 16px !important;
        }
        
        .context-bar-left {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
            min-width: 200px;
        }
        
        .context-bar-right {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .context-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            white-space: nowrap;
        }
        
        .context-select {
            min-width: 250px;
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            background: var(--surface);
            color: var(--text);
        }
        
        .context-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .context-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--muted);
        }
        
        .context-status-icon {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }
        
        /* ============================================
           GRID
           ============================================ */
        .grid-kpi {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)) !important;
            gap: 16px !important;
            margin-bottom: 24px !important;
        }
        
        .grid-charts {
            display: grid !important;
            grid-template-columns: 2fr 1fr !important;
            gap: 16px !important;
            margin-bottom: 24px !important;
        }
        
        .grid-tables {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)) !important;
            gap: 16px !important;
        }
        
        @media (max-width: 1024px) {
            .grid-charts {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 16px;
            }
            
            .grid-kpi {
                grid-template-columns: 1fr;
            }
            
            .grid-tables {
                grid-template-columns: 1fr;
            }
            
            .context-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .context-bar-left,
            .context-bar-right {
                flex-direction: column;
                align-items: stretch;
            }
        }
        
        /* ============================================
           TABELA
           ============================================ */
        .table-container {
            background: var(--surface) !important;
            border: 1px solid var(--border) !important;
            border-radius: var(--radius) !important;
            box-shadow: var(--shadow-sm) !important;
            overflow: hidden !important;
        }
        
        .table-header {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .table-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            margin: 0;
        }
        
        .table-subtitle {
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
            margin: 4px 0 0 0;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            padding: 12px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.02);
        }
        
        .table td {
            padding: 12px 16px;
            font-size: 14px;
            color: var(--text);
            border-bottom: 1px solid var(--border);
        }
        
        .table tbody tr {
            transition: background 0.15s ease;
        }
        
        .table tbody tr:hover {
            background: rgba(15, 23, 42, 0.02);
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-muted {
            color: var(--muted);
        }
        
        .font-bold {
            font-weight: 700;
        }
        
        .font-semibold {
            font-weight: 600;
        }
        
        /* ============================================
           GRÁFICO
           ============================================ */
        .chart-container {
            background: var(--surface) !important;
            border: 1px solid var(--border) !important;
            border-radius: var(--radius) !important;
            box-shadow: var(--shadow-sm) !important;
            padding: 16px !important;
        }
        
        .chart-header {
            margin-bottom: 16px;
        }
        
        .chart-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            margin: 0 0 4px 0;
        }
        
        .chart-subtitle {
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
            margin: 0;
        }
        
        .chart-bars {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 8px;
            height: 200px;
            margin-bottom: 16px;
        }
        
        .chart-bar {
            flex: 1;
            background: var(--primary);
            border-radius: 4px 4px 0 0;
            min-height: 4px;
            transition: all 0.2s ease;
            cursor: pointer;
            position: relative;
        }
        
        .chart-bar:hover {
            opacity: 0.8;
        }
        
        .chart-bar-label {
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 11px;
            color: var(--muted);
            font-weight: 500;
            white-space: nowrap;
        }
        
        .chart-summary {
            display: flex;
            justify-content: space-between;
            padding-top: 16px;
            border-top: 1px solid var(--border);
            font-size: 13px;
        }
        
        .chart-summary-item {
            text-align: center;
        }
        
        .chart-summary-label {
            color: var(--muted);
            font-weight: 500;
            margin-bottom: 4px;
        }
        
        .chart-summary-value {
            color: var(--text);
            font-weight: 700;
            font-size: 16px;
        }
        
        /* ============================================
           LISTA COMPACTA
           ============================================ */
        .compact-list {
            background: var(--surface) !important;
            border: 1px solid var(--border) !important;
            border-radius: var(--radius) !important;
            box-shadow: var(--shadow-sm) !important;
            padding: 16px !important;
        }
        
        .compact-list-header {
            margin-bottom: 16px;
        }
        
        .compact-list-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 8px;
            transition: background 0.15s ease;
        }
        
        .compact-list-item:hover {
            background: rgba(15, 23, 42, 0.02);
        }
        
        .compact-list-rank {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .compact-list-content {
            flex: 1;
            min-width: 0;
        }
        
        .compact-list-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 2px;
        }
        
        .compact-list-meta {
            font-size: 12px;
            color: var(--muted);
        }
        
        .compact-list-value {
            text-align: right;
        }
        
        .compact-list-value-main {
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
        }
        
        .compact-list-value-sub {
            font-size: 12px;
            color: var(--muted);
            margin-top: 2px;
        }
    </style>
    @endpush

    <div class="dashboard-container">
        <!-- TOPBAR -->
        <div class="topbar">
            <div class="topbar-left">
                <h1>Dashboard</h1>
                <p>Visão geral do seu negócio</p>
            </div>
            <div class="topbar-right">
                <div class="topbar-date">
                    <div class="topbar-date-day">{{ now()->format('d/m/Y') }}</div>
                    <div class="topbar-date-time">{{ now()->format('H:i') }}</div>
                </div>
            </div>
        </div>

        <!-- CONTEXT BAR -->
        <div class="context-bar">
            <div class="context-bar-left">
                @if(auth()->user()->isAdmin() && isset($stores) && $stores->count() > 0)
                    <label class="context-label">Loja</label>
                    <select
                        id="storeSelect"
                        name="storeSelect"
                        data-route="{{ route('dashboard.selectStore') }}"
                        class="context-select"
                        value="{{ $storeId ?? '' }}"
                    >
                        <option value="" {{ empty($storeId) ? 'selected' : '' }}>Todas as lojas</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ (string)($storeId ?? '') === (string)$store->id ? 'selected' : '' }}>
                                {{ $store->code }}@if($store->abbreviation)[{{ $store->abbreviation }}]@endif - {{ $store->name }}
                            </option>
                        @endforeach
                    </select>
                    @if(empty($storeId))
                        <span class="badge badge-warning">Obrigatório</span>
                    @else
                        <span class="badge badge-success">Loja: {{ $selectedStore->name ?? 'N/A' }}</span>
                    @endif
                @elseif(isset($selectedStore) && $selectedStore)
                    <div class="context-status">
                        <svg class="context-status-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <span>{{ $selectedStore->name }}</span>
                    </div>
                @endif
            </div>
            <div class="context-bar-right">
                <div class="context-status">
                    <span>Atualizado: {{ now()->format('H:i') }}</span>
                </div>
            </div>
        </div>

        <!-- Mensagens -->
        @if(session('success'))
            <div class="card" style="background: rgba(22, 163, 74, 0.1); border-color: var(--success); margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <svg style="width: 20px; height: 20px; color: var(--success);" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span style="color: var(--success); font-weight: 600;">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="card" style="background: rgba(239, 68, 68, 0.1); border-color: var(--danger); margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <svg style="width: 20px; height: 20px; color: var(--danger);" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span style="color: var(--danger); font-weight: 600;">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <!-- KPIs -->
        <div class="grid-kpi">
            <!-- Vendas Hoje -->
            <div class="kpi-card" style="position: relative;">
                <div class="accent-strip success"></div>
                <div class="kpi-card-header">
                    <div class="kpi-icon" style="background: rgba(22, 163, 74, 0.1);">
                        <svg style="width: 20px; height: 20px; color: var(--success);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="kpi-label">Vendas Hoje</span>
                </div>
                <div class="kpi-value">R$ {{ number_format($salesToday ?? 0, 2, ',', '.') }}</div>
                <div class="kpi-detail">{{ $salesTodayCount ?? 0 }} {{ $salesTodayCount == 1 ? 'venda' : 'vendas' }}</div>
            </div>

            <!-- Venda Entrada Hoje -->
            <div class="kpi-card" style="position: relative;">
                <div class="accent-strip success"></div>
                <div class="kpi-card-header">
                    <div class="kpi-icon" style="background: rgba(22, 163, 74, 0.1);">
                        <svg style="width: 20px; height: 20px; color: var(--success);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <span class="kpi-label">Venda Entrada Hoje</span>
                </div>
                <div class="kpi-value">R$ {{ number_format($salesTodayCash ?? 0, 2, ',', '.') }}</div>
                <div class="kpi-detail">{{ $salesTodayCashCount ?? 0 }} {{ $salesTodayCashCount == 1 ? 'venda' : 'vendas' }} à vista</div>
            </div>

            <!-- Vendas do Mês -->
            <div class="kpi-card" style="position: relative;">
                <div class="accent-strip primary"></div>
                <div class="kpi-card-header">
                    <div class="kpi-icon" style="background: rgba(37, 99, 235, 0.1);">
                        <svg style="width: 20px; height: 20px; color: var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <span class="kpi-label">Vendas Mês</span>
                </div>
                <div class="kpi-value">R$ {{ number_format($salesMonth ?? 0, 2, ',', '.') }}</div>
                <div class="kpi-detail">{{ $salesMonthCount ?? 0 }} {{ $salesMonthCount == 1 ? 'venda' : 'vendas' }}</div>
            </div>

            <!-- Venda Entrada Mês -->
            <div class="kpi-card" style="position: relative;">
                <div class="accent-strip primary"></div>
                <div class="kpi-card-header">
                    <div class="kpi-icon" style="background: rgba(37, 99, 235, 0.1);">
                        <svg style="width: 20px; height: 20px; color: var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <span class="kpi-label">Venda Entrada Mês</span>
                </div>
                <div class="kpi-value">R$ {{ number_format($salesMonthCash ?? 0, 2, ',', '.') }}</div>
                <div class="kpi-detail">{{ $salesMonthCashCount ?? 0 }} {{ $salesMonthCashCount == 1 ? 'venda' : 'vendas' }} à vista</div>
            </div>

            <!-- Prestações de Carnê em Aberto -->
            <div class="kpi-card" style="position: relative;">
                <div class="accent-strip warning"></div>
                <div class="kpi-card-header">
                    <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.1);">
                        <svg style="width: 20px; height: 20px; color: var(--warning);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <span class="kpi-label">Prestação Carnê</span>
                </div>
                <div class="kpi-value">R$ {{ number_format($carneOpen ?? 0, 2, ',', '.') }}</div>
                <div class="kpi-detail">Em aberto</div>
            </div>
        </div>

        <!-- Gráficos e Estatísticas Financeiras -->
        <div class="grid-charts">
            <!-- Gráfico de Vendas -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3 class="chart-title">Vendas dos Últimos 7 Dias</h3>
                    <p class="chart-subtitle">Evolução diária de vendas</p>
                </div>
                @if(isset($salesChart) && count($salesChart) > 0)
                    @php
                        // Garantir que $salesChart seja um array
                        $salesChartArray = is_array($salesChart) ? $salesChart : (is_object($salesChart) && method_exists($salesChart, 'toArray') ? $salesChart->toArray() : []);
                        if (empty($salesChartArray)) {
                            $salesChartArray = [];
                        }
                        // Calcular valores uma única vez fora do loop
                        $maxValue = !empty($salesChartArray) ? max(array_column($salesChartArray, 'total')) : 0;
                        $total7Days = !empty($salesChartArray) ? array_sum(array_column($salesChartArray, 'total')) : 0;
                        $avg7Days = !empty($salesChartArray) ? ($total7Days / count($salesChartArray)) : 0;
                        // Encontrar o melhor dia corretamente
                        $bestDay = !empty($salesChartArray) ? $salesChartArray[0] : ['total' => 0];
                        foreach ($salesChartArray as $dayItem) {
                            if (isset($dayItem['total']) && $dayItem['total'] > $bestDay['total']) {
                                $bestDay = $dayItem;
                            }
                        }
                    @endphp
                    <div class="chart-bars">
                        @foreach($salesChartArray as $day)
                            @php
                                $height = $maxValue > 0 ? ($day['total'] / $maxValue) * 100 : 0;
                            @endphp
                            <div class="chart-bar" style="height: {{ $height }}%;" title="R$ {{ number_format($day['total'], 2, ',', '.') }}">
                                <div class="chart-bar-label">{{ $day['date'] }}</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="chart-summary">
                        <div class="chart-summary-item">
                            <div class="chart-summary-label">Total 7 dias</div>
                            <div class="chart-summary-value">R$ {{ number_format($total7Days, 2, ',', '.') }}</div>
                        </div>
                        <div class="chart-summary-item">
                            <div class="chart-summary-label">Média diária</div>
                            <div class="chart-summary-value">R$ {{ number_format($avg7Days, 2, ',', '.') }}</div>
                        </div>
                        <div class="chart-summary-item">
                            <div class="chart-summary-label">Melhor dia</div>
                            <div class="chart-summary-value">R$ {{ number_format($bestDay['total'], 2, ',', '.') }}</div>
                        </div>
                    </div>
                @else
                    <div style="display: flex; align-items: center; justify-content: center; height: 200px; color: var(--muted);">
                        <p>Nenhum dado disponível</p>
                    </div>
                @endif
            </div>

            <!-- Estatísticas Financeiras -->
            <div style="display: flex; flex-direction: column; gap: 16px;">
                @if(isset($receivablesTotal) && $receivablesTotal > 0)
                    <div class="card" style="position: relative;">
                        <div class="accent-strip success"></div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                            <h3 style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted); margin: 0;">A Receber</h3>
                            <svg style="width: 20px; height: 20px; color: var(--success);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div style="font-size: 24px; font-weight: 700; color: var(--text); margin-bottom: 4px;">R$ {{ number_format($receivablesTotal, 2, ',', '.') }}</div>
                        <div style="font-size: 13px; color: var(--muted);">Contas pendentes</div>
                    </div>
                @endif

                @if(isset($payablesTotal) && $payablesTotal > 0)
                    <div class="card" style="position: relative;">
                        <div class="accent-strip danger"></div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                            <h3 style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted); margin: 0;">A Pagar</h3>
                            <svg style="width: 20px; height: 20px; color: var(--danger);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div style="font-size: 24px; font-weight: 700; color: var(--text); margin-bottom: 4px;">R$ {{ number_format($payablesTotal, 2, ',', '.') }}</div>
                        <div style="font-size: 13px; color: var(--muted);">Contas pendentes</div>
                    </div>
                @endif

            </div>
        </div>

        <!-- Tabelas -->
        <div class="grid-tables">
            <!-- Contas a Pagar Próximas -->
            @if(isset($upcomingPayables) && $upcomingPayables->count() > 0)
                <div class="table-container">
                    <div class="table-header">
                        <div>
                            <h3 class="table-title">Contas a Pagar</h3>
                            <p class="table-subtitle">Contas que estão para vencer nos próximos dias</p>
                        </div>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fornecedor</th>
                                <th>Documento</th>
                                <th>Vencimento</th>
                                <th>Categoria</th>
                                <th>Status</th>
                                <th class="text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($upcomingPayables as $payable)
                                <tr>
                                    <td class="font-semibold">{{ $payable->supplier->name ?? 'N/A' }}</td>
                                    <td class="text-muted">{{ $payable->document_no ?? '-' }}</td>
                                    <td class="text-muted">
                                        {{ $payable->due_date ? $payable->due_date->format('d/m/Y') : '' }}
                                        @if($payable->due_date)
                                            @php
                                                $dueDate = \Carbon\Carbon::parse($payable->due_date);
                                                $today = \Carbon\Carbon::today();
                                            @endphp
                                            @if($dueDate->isPast() && $payable->status !== 'paid')
                                                <span class="badge badge-danger">Vencido</span>
                                            @elseif($dueDate->isToday())
                                                <span class="badge badge-warning">Hoje</span>
                                            @elseif($dueDate->diffInDays($today) <= 3)
                                                <span class="badge badge-warning">Próximo</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if($payable->category)
                                            <span class="badge badge-muted">{{ $payable->category->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payable->status == 'paid')
                                            <span class="badge badge-success">Pago</span>
                                        @elseif($payable->status == 'partial')
                                            <span class="badge badge-warning">Parcial</span>
                                        @else
                                            <span class="badge badge-danger">Aberto</span>
                                        @endif
                                    </td>
                                    <td class="text-right font-bold">R$ {{ number_format($payable->balance_amount ?? 0, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="table-container">
                    <div class="table-header">
                        <div>
                            <h3 class="table-title">Contas a Pagar</h3>
                            <p class="table-subtitle">Contas que estão para vencer nos próximos dias</p>
                        </div>
                    </div>
                    <div style="padding: 24px; text-align: center; color: var(--muted);">
                        <p>Nenhuma conta a pagar encontrada para os próximos 30 dias.</p>
                    </div>
                </div>
            @endif

            <!-- Contas a Receber do Mês -->
            @if(isset($monthReceivables) && $monthReceivables->count() > 0)
                <div class="table-container">
                    <div class="table-header">
                        <div>
                            <h3 class="table-title">Contas a Receber Este Mês</h3>
                            <p class="table-subtitle">Listagem para controle de cobranças</p>
                        </div>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Vencimento</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th class="text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($monthReceivables as $receivable)
                                <tr>
                                    <td class="font-semibold">{{ $receivable->customer->name ?? 'N/A' }}</td>
                                    <td class="text-muted">
                                        {{ $receivable->due_date ? $receivable->due_date->format('d/m/Y') : '' }}
                                        @if($receivable->due_date && $receivable->due_date->isPast() && $receivable->status !== 'paid')
                                            <span class="badge badge-danger">Vencido</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($receivable->billing_type)
                                            <span class="badge badge-muted">
                                                @if($receivable->billing_type == 'carne') 📋 Carnê
                                                @elseif($receivable->billing_type == 'crediario') 💳 Crediário
                                                @elseif($receivable->billing_type == 'boleto') 🧾 Boleto
                                                @else {{ ucfirst($receivable->billing_type) }}
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($receivable->status == 'paid')
                                            <span class="badge badge-success">Pago</span>
                                        @elseif($receivable->status == 'partial')
                                            <span class="badge badge-warning">Parcial</span>
                                        @else
                                            <span class="badge badge-danger">Aberto</span>
                                        @endif
                                    </td>
                                    <td class="text-right font-bold">R$ {{ number_format($receivable->balance_amount ?? 0, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="table-container">
                    <div class="table-header">
                        <div>
                            <h3 class="table-title">Contas a Receber Este Mês</h3>
                            <p class="table-subtitle">Listagem para controle de cobranças</p>
                        </div>
                    </div>
                    <div style="padding: 24px; text-align: center; color: var(--muted);">
                        <p>Nenhuma conta a receber encontrada para este mês.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        // Verificar se CSS foi aplicado
        (function() {
            setTimeout(function() {
                const testEl = document.querySelector('.dashboard-container');
                if (testEl) {
                    const styles = window.getComputedStyle(testEl);
                    const bg = styles.backgroundColor;
                    // Se o background não for o esperado, forçar reload
                    if (bg === 'rgba(0, 0, 0, 0)' || bg === 'rgb(248, 250, 252)') {
                        console.warn('CSS não aplicado, forçando reload...');
                        window.location.reload(true);
                    }
                }
            }, 500);
        })();
        
        // ============================================
        // CONFIGURAÇÃO DO SELECT DE LOJA
        // ============================================
        (function() {
            'use strict';
            
            function navigateToStoreSelection(storeId) {
                try {
                    const storeSelect = document.getElementById('storeSelect');
                    const baseUrl = storeSelect?.dataset.route || '{{ route('dashboard.selectStore') }}';
                    const url = storeId ? baseUrl + '?store_id=' + encodeURIComponent(storeId) : baseUrl;
                    
                    const logData = {
                        timestamp: new Date().toISOString(),
                        action: 'navigateToStoreSelection',
                        storeId: storeId,
                        url: url,
                    };
                    
                    console.log('🔍 [Dashboard] Navegando para seleção de loja:', url);
                    console.log('🔍 [Dashboard] Store ID selecionado:', storeId);
                    
                    // Salvar log no localStorage para persistir após refresh
                    try {
                        const existingLogs = JSON.parse(localStorage.getItem('dashboard_logs') || '[]');
                        existingLogs.push(logData);
                        // Manter apenas os últimos 20 logs
                        if (existingLogs.length > 20) {
                            existingLogs.shift();
                        }
                        localStorage.setItem('dashboard_logs', JSON.stringify(existingLogs));
                        localStorage.setItem('dashboard_last_action', JSON.stringify(logData));
                    } catch (e) {
                        console.warn('⚠️ [Dashboard] Erro ao salvar log no localStorage:', e);
                    }
                    
                    // Usar window.location.href para garantir que a navegação funcione corretamente
                    // e que o histórico seja mantido
                    window.location.href = url;
                } catch (error) {
                    console.error('❌ [Dashboard] Erro ao navegar:', error);
                    alert('Erro ao selecionar loja. Por favor, recarregue a página e tente novamente.');
                }
            }
            
            function setupStoreSelectListener() {
                const storeSelect = document.getElementById('storeSelect');
                
                if (!storeSelect) {
                    return;
                }
                
                if (storeSelect.dataset.listenerConfigured === 'true') {
                    return;
                }
                
                storeSelect.dataset.listenerConfigured = 'true';
                
                const parent = storeSelect.parentNode;
                const newSelect = storeSelect.cloneNode(true);
                newSelect.dataset.listenerConfigured = 'true';
                parent.replaceChild(newSelect, storeSelect);
                
                const finalSelect = document.getElementById('storeSelect');
                if (!finalSelect) {
                    return;
                }
                
                finalSelect.addEventListener('change', function handleStoreChange(e) {
                    if (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                    }
                    
                    const storeId = this.value;
                    
                    const changeLog = {
                        timestamp: new Date().toISOString(),
                        action: 'storeSelectChange',
                        storeId: storeId,
                        selectValue: this.value,
                        selectOptions: Array.from(this.options).map(opt => ({ value: opt.value, text: opt.text })),
                    };
                    
                    console.log('🔍 [Dashboard] Loja selecionada no select:', storeId);
                    console.log('🔍 [Dashboard] Detalhes do select:', changeLog);
                    
                    // Salvar log no localStorage
                    try {
                        const existingLogs = JSON.parse(localStorage.getItem('dashboard_logs') || '[]');
                        existingLogs.push(changeLog);
                        if (existingLogs.length > 20) {
                            existingLogs.shift();
                        }
                        localStorage.setItem('dashboard_logs', JSON.stringify(existingLogs));
                        localStorage.setItem('dashboard_last_action', JSON.stringify(changeLog));
                    } catch (e) {
                        console.warn('⚠️ [Dashboard] Erro ao salvar log:', e);
                    }
                    
                    // Feedback visual
                    this.disabled = true;
                    this.style.opacity = '0.6';
                    this.style.cursor = 'wait';
                    
                    // Navegar imediatamente (sem delay para evitar problemas)
                    navigateToStoreSelection(storeId);
                    
                    return false;
                }, true);
            }
            
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', setupStoreSelectListener);
            } else {
                setupStoreSelectListener();
            }
            
            setTimeout(setupStoreSelectListener, 200);
        })();
        
        // Debug: Exibir dados do backend
        (function() {
            const backendData = {
                storeId: {{ $storeId ?? 'null' }},
                selectedStore: {{ $selectedStore ? json_encode($selectedStore->name) : 'null' }},
                storesCount: {{ $stores->count() ?? 0 }},
                isAdmin: {{ auth()->user()->isAdmin() ? 'true' : 'false' }},
            };
            console.log('🔍 [Dashboard] Dados do Backend:', backendData);
            console.log('🔍 [Dashboard] StoreId do Backend:', backendData.storeId);
            console.log('🔍 [Dashboard] SelectedStore do Backend:', backendData.selectedStore);
        })();
        
        // Verificar logs anteriores e exibir
        (function() {
            try {
                const lastAction = localStorage.getItem('dashboard_last_action');
                if (lastAction) {
                    const action = JSON.parse(lastAction);
                    console.log('📋 [Dashboard] Última ação antes do refresh:', action);
                }
                
                const allLogs = localStorage.getItem('dashboard_logs');
                if (allLogs) {
                    const logs = JSON.parse(allLogs);
                    console.log('📋 [Dashboard] Histórico de ações (últimas ' + logs.length + '):', logs);
                }
            } catch (e) {
                console.warn('⚠️ [Dashboard] Erro ao ler logs do localStorage:', e);
            }
        })();
        
        // Verificar se há loja selecionada na URL ou sessão após carregamento
        (function() {
            setTimeout(function() {
                const storeSelect = document.getElementById('storeSelect');
                const urlParams = new URLSearchParams(window.location.search);
                const storeIdFromUrl = urlParams.get('store_id');
                const expectedValue = '{{ $storeId ?? '' }}';
                
                const backendStoreId = {{ $storeId ?? 'null' }};
                const debugInfo = {
                    'current_value': storeSelect ? storeSelect.value : 'N/A',
                    'expected_value': expectedValue,
                    'backend_store_id': backendStoreId,
                    'store_id_from_url': storeIdFromUrl,
                    'match': storeSelect ? (storeSelect.value === expectedValue) : false,
                    'url': window.location.href,
                    'backend_selected_store': {{ $selectedStore ? json_encode($selectedStore->name) : 'null' }},
                };
                
                // Salvar estado atual no localStorage
                try {
                    localStorage.setItem('dashboard_current_state', JSON.stringify({
                        timestamp: new Date().toISOString(),
                        ...debugInfo
                    }));
                } catch (e) {
                    console.warn('⚠️ [Dashboard] Erro ao salvar estado:', e);
                }
                
                console.log('🔍 [Dashboard] Verificando loja selecionada', debugInfo);
                console.log('🔍 [Dashboard] Backend StoreId (raw):', backendStoreId);
                console.log('🔍 [Dashboard] Backend StoreId (type):', typeof backendStoreId);
                
                if (storeSelect) {
                    // Se não estiver selecionado corretamente, atualizar
                    if (expectedValue && storeSelect.value !== expectedValue) {
                        console.log('⚠️ [Dashboard] Loja não está selecionada corretamente, corrigindo...');
                        storeSelect.value = expectedValue;
                        
                        // Verificar se foi atualizado
                        if (storeSelect.value === expectedValue) {
                            console.log('✅ [Dashboard] Loja corrigida com sucesso');
                        } else {
                            console.error('❌ [Dashboard] Erro ao corrigir loja selecionada');
                        }
                    } else if (!expectedValue) {
                        console.warn('⚠️ [Dashboard] Nenhuma loja selecionada no backend (storeId vazio)');
                        if (storeIdFromUrl) {
                            console.warn('⚠️ [Dashboard] Mas há store_id na URL:', storeIdFromUrl);
                        }
                    }
                } else {
                    console.warn('⚠️ [Dashboard] Select de loja não encontrado');
                }
            }, 500);
        })();
    </script>
</x-app-layout>
