import assert from "node:assert/strict";
import test from "node:test";
import { build } from "esbuild";

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
