import assert from "node:assert/strict";
import test from "node:test";
import { build } from "esbuild";

const buildResult = await build({
  entryPoints: ["resources/js/services/bookingApi.js"],
  bundle: true,
  format: "esm",
  platform: "browser",
  write: false,
});

const moduleUrl = `data:text/javascript;base64,${Buffer.from(
  buildResult.outputFiles[0].text
).toString("base64")}`;

let importSequence = 0;

async function loadBookingApi(bookingConfig) {
  globalThis.window = {
    config: {
      booking: bookingConfig,
    },
  };

  importSequence += 1;

  return (await import(`${moduleUrl}#${importSequence}`)).default;
}

test("direct Booking API client uses the public server configuration and keeps its 30 second timeout", async () => {
  const bookingApi = await loadBookingApi({
    apiBaseUrl: "https://booking.test/api/v2/",
  });

  assert.equal(bookingApi.baseURL, "https://booking.test/api/v2");
  assert.equal(
    bookingApi.client.getUri({ url: "/cities" }),
    "https://booking.test/api/v2/cities"
  );
  assert.equal(bookingApi.client.defaults.timeout, 30000);
});

test("Booking API timeout is reported as a retryable service delay", async () => {
  const bookingApi = await loadBookingApi({
    apiBaseUrl: "https://booking.test/api/v1",
  });

  const error = bookingApi.handleError({
    code: "ECONNABORTED",
    request: {},
  });

  assert.equal(
    error.message,
    "Сервис записи не ответил вовремя. Попробуйте ещё раз."
  );
});
