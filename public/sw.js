// This project doesn't use a service worker. If a stale registration exists
// for the local origin, replace it with this noop worker, clear old caches,
// and immediately unregister to restore normal Vite/Laravel dev behavior.
self.addEventListener("install", () => {
  self.skipWaiting();
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    (async () => {
      const cacheKeys = await caches.keys();

      await Promise.all(cacheKeys.map((key) => caches.delete(key)));
      await self.registration.unregister();

      const clients = await self.clients.matchAll({
        type: "window",
        includeUncontrolled: true,
      });

      await Promise.all(clients.map((client) => client.navigate(client.url)));
    })()
  );
});
