import assert from "node:assert/strict";
import test from "node:test";
import { build } from "esbuild";
import { readFile } from "node:fs/promises";
import vm from "node:vm";

const buildResult = await build({
  entryPoints: ["resources/js/utilities/doctorPrice.js"],
  bundle: true,
  format: "esm",
  platform: "node",
  write: false,
});

const moduleUrl = `data:text/javascript;base64,${Buffer.from(
  buildResult.outputFiles[0].text
).toString("base64")}`;
const priceModule = await import(moduleUrl);

function birthDateYearsAgo(years) {
  const today = new Date();
  const birthYear = today.getFullYear() - years;
  const lastDayOfBirthMonth = new Date(
    birthYear,
    today.getMonth() + 1,
    0
  ).getDate();
  const birthDate = new Date(
    birthYear,
    today.getMonth(),
    Math.min(today.getDate(), lastDayOfBirthMonth)
  );

  return [
    birthDate.getFullYear(),
    String(birthDate.getMonth() + 1).padStart(2, "0"),
    String(birthDate.getDate()).padStart(2, "0"),
  ].join("-");
}

test("branch price uses age-specific value when both prices are configured", () => {
  assert.equal(typeof priceModule.getBranchAgeAwarePrice, "function");

  const branch = { price: "1800", price_child: "1200" };

  assert.equal(
    priceModule.getBranchAgeAwarePrice(branch, birthDateYearsAgo(17)),
    "1200"
  );
  assert.equal(
    priceModule.getBranchAgeAwarePrice(branch, birthDateYearsAgo(18)),
    "1800"
  );
});

test("branch prices only apply to their matching age category", () => {
  assert.equal(
    priceModule.getBranchAgeAwarePrice(
      { price: "1800", price_child: "" },
      birthDateYearsAgo(10)
    ),
    null
  );
  assert.equal(
    priceModule.getBranchAgeAwarePrice(
      { price: "", price_child: "1200" },
      birthDateYearsAgo(30)
    ),
    null
  );
  assert.equal(
    priceModule.getBranchAgeAwarePrice(
      { price: "1800", price_child: "" },
      birthDateYearsAgo(30)
    ),
    "1800"
  );
  assert.equal(
    priceModule.getBranchAgeAwarePrice(
      { price: "", price_child: "1200" },
      birthDateYearsAgo(10)
    ),
    "1200"
  );
});

test("a missing age-specific branch price falls back to the matching doctor price", () => {
  const doctor = {
    extra: {
      price: "2500",
      price_child: "2000",
    },
  };

  assert.equal(
    priceModule.getDoctorDisplayPrice(
      doctor,
      { price: "1800", price_child: "" },
      birthDateYearsAgo(10)
    ),
    "2000"
  );
  assert.equal(
    priceModule.getDoctorDisplayPrice(
      doctor,
      { price: "", price_child: "1200" },
      birthDateYearsAgo(30)
    ),
    "2500"
  );
});

test("doctor exclusion flag disables branch promotion for both age categories", () => {
  assert.equal(typeof priceModule.resolveDoctorDisplayPrice, "function");

  const doctor = {
    extra: {
      price: "2500",
      price_child: "2000",
      exclude_from_branch_promo_price: true,
    },
  };
  const branch = { price: "1800", price_child: "1200" };

  assert.deepEqual(
    priceModule.resolveDoctorDisplayPrice(
      doctor,
      branch,
      birthDateYearsAgo(10)
    ),
    { price: "2000", source: "doctor" }
  );
  assert.deepEqual(
    priceModule.resolveDoctorDisplayPrice(
      doctor,
      branch,
      birthDateYearsAgo(30)
    ),
    { price: "2500", source: "doctor" }
  );
});

test("excluded doctor without a price does not fall back to branch promotion", () => {
  const doctor = {
    extra: {
      exclude_from_branch_promo_price: true,
    },
  };
  const branch = { price: "1800", price_child: "1200" };

  assert.equal(
    priceModule.getDoctorDisplayPrice(
      doctor,
      branch,
      birthDateYearsAgo(30)
    ),
    null
  );
  assert.equal(
    priceModule.getDoctorDisplayPriceSource(
      doctor,
      branch,
      birthDateYearsAgo(30)
    ),
    null
  );
});

const periods = [
  { price: "2500", starts_on: null, ends_on: null },
  { price: "3000", starts_on: "2026-11-01", ends_on: "2026-11-30" },
];

test("appointment date selects the period and the end is inclusive", () => {
  const doctor = { extra: { price: "2500", price_periods: { price: periods } } };
  const resolve = (appointmentDate) => priceModule.getDoctorDisplayPrice(
    doctor, null, birthDateYearsAgo(30), { appointmentDate }
  );
  assert.equal(resolve("2026-10-31"), "2500");
  assert.equal(resolve("2026-11-01"), "3000");
  assert.equal(resolve(new Date(2026, 10, 30)), "3000");
  assert.equal(resolve("2026-12-01"), null);
});

test("a future price without an old price remains hidden before its start", () => {
  const doctor = { extra: { price_periods: { price: [periods[1]] } } };
  assert.equal(priceModule.getDoctorDisplayPrice(doctor, null, birthDateYearsAgo(30), { appointmentDate: "2026-10-31" }), null);
  assert.equal(priceModule.getDoctorDisplayPrice(doctor, null, birthDateYearsAgo(30), { appointmentDate: "2026-11-01" }), "3000");
});

test("expired branch price falls back to doctor price on the same appointment date", () => {
  const doctor = { extra: { price: "4000", price_child: "2000" } };
  const branch = { price: "9999", price_periods: { price: [periods[1]] } };
  assert.equal(priceModule.getDoctorDisplayPrice(doctor, branch, birthDateYearsAgo(30), { appointmentDate: "2026-11-30" }), "3000");
  assert.equal(priceModule.getDoctorDisplayPrice(doctor, branch, birthDateYearsAgo(30), { appointmentDate: "2026-12-01" }), "4000");
});

test("explicitly removed age-specific doctor price does not borrow another category", () => {
  const doctor = { extra: { price: "2500", price_child: "1500", price_periods: { price: [] } } };
  assert.equal(priceModule.getDoctorDisplayPrice(doctor, null, birthDateYearsAgo(30), { appointmentDate: "2026-11-01" }), null);
  assert.equal(priceModule.getDoctorDisplayPrice(doctor, null, birthDateYearsAgo(10), { appointmentDate: "2026-11-01" }), "1500");
});

test("all prices may be absent and legacy one-category fallback is preserved", () => {
  assert.deepEqual(priceModule.resolveDoctorDisplayPrice({ extra: {} }, {}, birthDateYearsAgo(30)), { price: null, source: null });
  assert.equal(priceModule.getDoctorDisplayPrice({ extra: { price_child: "1500" } }, null, birthDateYearsAgo(30)), "1500");
});

test("doctor exclusion still ignores dated branch prices", () => {
  const doctor = { extra: { price: "4000", exclude_from_branch_promo_price: true } };
  assert.equal(priceModule.getDoctorDisplayPrice(doctor, { price_periods: { price: periods } }, birthDateYearsAgo(30), { appointmentDate: "2026-11-01" }), "4000");
});

test("adult and child periods are independent", () => {
  const doctor = { extra: { price: "2500", price_child: "1500", price_periods: { price: periods, price_child: [{ price: "1700", starts_on: "2026-12-01", ends_on: null }, { price: "1500", starts_on: null, ends_on: null }] } } };
  assert.equal(priceModule.getDoctorDisplayPrice(doctor, null, birthDateYearsAgo(10), { appointmentDate: "2026-11-01" }), "1500");
  assert.equal(priceModule.getDoctorDisplayPrice(doctor, null, birthDateYearsAgo(10), { appointmentDate: "2026-12-01" }), "1700");
});

test("several scheduled prices replace earlier periods even when their end is later", () => {
  const doctor = { extra: { price: "2000", price_periods: { price: [
    { price: "2000", starts_on: "2026-10-12", ends_on: null },
    { price: "2000", starts_on: null, ends_on: null },
    { price: "3000", starts_on: "2026-10-06", ends_on: "2026-12-01" },
    { price: "4000", starts_on: "2026-10-07", ends_on: "2026-10-08" },
  ] } } };
  for (const [appointmentDate, expected] of [
    ["2026-10-05", "2000"], ["2026-10-06", "3000"], ["2026-10-07", "4000"],
    ["2026-10-08", "4000"], ["2026-10-09", null], ["2026-10-12", "2000"], ["2027-01-01", "2000"],
  ]) {
    assert.equal(priceModule.getDoctorDisplayPrice(doctor, null, birthDateYearsAgo(30), { appointmentDate }), expected);
  }
});

async function componentOptions(name) {
  const source = await readFile(`resources/js/components/BookingWidgetV3/components/${name}.vue`, "utf8");
  const script = source.match(/<script>([\s\S]*?)<\/script>/)[1];
  const withoutImports = script.replace(/^import[\s\S]*?from\s+["'][^"']+["'];/gm, "");
  const context = { getDoctorDisplayPrice: priceModule.getDoctorDisplayPrice };
  vm.runInNewContext(withoutImports.replace("export default", "globalThis.component ="), context);
  return context.component;
}

test("doctor selection and both schedule components use the selected appointment date", async () => {
  const doctor = { extra: { price: "2500", price_periods: { price: periods } } };
  const state = { doctor, selectedBranch: null, patientBirthDate: birthDateYearsAgo(30), selectedDate: new Date(2026, 10, 1), doctorBranch: () => null };
  const select = await componentOptions("DoctorSelectStep");
  const doctorSchedule = await componentOptions("DoctorScheduleStep");
  const clinicSchedule = await componentOptions("ClinicScheduleStep");
  assert.equal(select.methods.doctorDisplayPrice.call(state, doctor), "3000");
  assert.equal(doctorSchedule.computed.doctorPrice.call(state), "3000");
  assert.equal(clinicSchedule.methods.doctorPrice.call(state, doctor), "3000");
  state.selectedDate = new Date(2026, 9, 31);
  assert.equal(doctorSchedule.computed.doctorPrice.call(state), "2500");
  assert.equal(clinicSchedule.methods.doctorPrice.call(state, doctor), "2500");
});
