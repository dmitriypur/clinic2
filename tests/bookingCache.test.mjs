import assert from "node:assert/strict";
import test from "node:test";
import { build } from "esbuild";

const buildResult = await build({
  entryPoints: ["resources/js/components/BookingWidgetV3/utils/cacheUtils.js"],
  bundle: true,
  format: "esm",
  platform: "node",
  write: false,
});

const moduleUrl = `data:text/javascript;base64,${Buffer.from(
  buildResult.outputFiles[0].text
).toString("base64")}`;
const cacheModule = await import(moduleUrl);

test("clearObjectCaches removes stale entries from every provided cache", () => {
  assert.equal(typeof cacheModule.clearObjectCaches, "function");

  const doctors = { city: { ts: 1, data: [{ id: 1 }] } };
  const branches = { city: { ts: 1, data: [{ id: 2 }] } };

  cacheModule.clearObjectCaches(doctors, branches, null);

  assert.deepEqual(doctors, {});
  assert.deepEqual(branches, {});
});

test("versioned keys isolate responses started before a cache reset", () => {
  const requestKeyBeforeReset = cacheModule.buildVersionedCacheKey(1, [
    "city-1",
    "adult",
  ]);
  const lookupKeyAfterReset = cacheModule.buildVersionedCacheKey(2, [
    "city-1",
    "adult",
  ]);

  assert.notEqual(requestKeyBeforeReset, lookupKeyAfterReset);
});

test("a response started before reset cannot overwrite current state", async () => {
  let currentVersion = 1;
  let doctors = ["fresh"];
  const requestVersion = currentVersion;
  const delayedResponse = Promise.resolve(["stale"]);

  currentVersion += 1;
  const response = await delayedResponse;
  if (cacheModule.isCurrentCacheVersion(requestVersion, currentVersion)) {
    doctors = response;
  }

  assert.deepEqual(doctors, ["fresh"]);
});

test("widget reset clears every loading flag", () => {
  assert.equal(typeof cacheModule.resetBookingLoadingFlags, "function");

  const state = {
    loadingClinics: true,
    loadingCityBranches: true,
    loadingDoctors: true,
    loadingDateFlowDoctors: true,
    loadingSlots: true,
    loadingDoctorFlowBranches: true,
    currentStep: "doctor-schedule",
  };

  cacheModule.resetBookingLoadingFlags(state);

  assert.deepEqual(state, {
    loadingClinics: false,
    loadingCityBranches: false,
    loadingDoctors: false,
    loadingDateFlowDoctors: false,
    loadingSlots: false,
    loadingDoctorFlowBranches: false,
    currentStep: "doctor-schedule",
  });
});
