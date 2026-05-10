const CACHE_NAME = 'hpoculos-v7';

// Instalar Service Worker
self.addEventListener('install', (event) => {
  console.log('[SW] Instalando service worker v7');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(() => {
        console.log('[SW] Cache aberto');
        return self.skipWaiting();
      })
  );
});

// Ativar Service Worker
self.addEventListener('activate', (event) => {
  console.log('[SW] Ativando service worker v7');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('[SW] Removendo cache antigo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      return self.clients.claim();
    })
  );
});

// Interceptar requisições
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  const currentOrigin = self.location.origin;
  
  // PRIORIDADE 1: NUNCA interceptar requisições não-GET
  if (event.request.method !== 'GET') {
    return;
  }

  // PRIORIDADE 2: NUNCA interceptar recursos de domínios externos
  // Esta é a verificação mais importante para evitar erros de CORS
  if (url.origin !== currentOrigin) {
    // Recursos externos (CDNs, fonts, etc) devem passar direto
    // SEM NENHUMA interceptação do service worker
    return;
  }

  // PRIORIDADE 3: NUNCA interceptar rotas dinâmicas ou que fazem redirect
  const pathname = url.pathname.toLowerCase();
  const isDynamicRoute = 
    pathname.includes('select-store') ||
    pathname.includes('selectstore') ||
    pathname.includes('gerar-numero') ||
    pathname.includes('generate-number') ||
    pathname === '/dashboard' ||
    pathname === '/' ||
    pathname.includes('/login') ||
    pathname.includes('/logout') ||
    pathname.includes('/register') ||
    pathname.includes('/api/') ||
    url.searchParams.has('store_id');

  if (isDynamicRoute) {
    // Rotas dinâmicas devem passar direto sem interceptação
    return;
  }

  // PRIORIDADE 4: Apenas cachear recursos estáticos compilados do próprio domínio
  // Apenas arquivos em /build/ ou /assets/ (gerados pelo Vite)
  const isBuildAsset = pathname.startsWith('/build/') || pathname.startsWith('/assets/');
  
  if (!isBuildAsset) {
    // Não cachear nada além de assets compilados
    return;
  }

  // Apenas aqui: cachear recursos estáticos compilados
  event.respondWith(
    caches.match(event.request)
      .then((cachedResponse) => {
        if (cachedResponse) {
          return cachedResponse;
        }

        // Buscar do servidor
        return fetch(event.request, {
          mode: 'same-origin',
          cache: 'no-cache'
        }).then((response) => {
          // Apenas cachear respostas 200 OK válidas
          if (response && response.status === 200 && response.type === 'basic') {
            const responseToCache = response.clone();
            // Cachear em background (não bloquear)
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, responseToCache).catch(() => {
                // Ignorar erros de cache silenciosamente
              });
            });
          }
          return response;
        }).catch(() => {
          // Em caso de erro, retornar resposta vazia ou do cache
          return caches.match(event.request);
        });
      })
  );
});
