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

test("a single configured branch price applies to every age", () => {
  assert.equal(
    priceModule.getBranchAgeAwarePrice(
      { price: "1800", price_child: "" },
      birthDateYearsAgo(10)
    ),
    "1800"
  );
  assert.equal(
    priceModule.getBranchAgeAwarePrice(
      { price: "", price_child: "1200" },
      birthDateYearsAgo(30)
    ),
    "1200"
  );
});

test("branch price keeps priority and falls back to the doctor only when both branch prices are empty", () => {
  const doctor = {
    extra: {
      price: "2500",
      price_child: "2000",
    },
  };

  assert.equal(
    priceModule.getDoctorDisplayPrice(
      doctor,
      { price: "1800", price_child: "1200" },
      birthDateYearsAgo(10)
    ),
    "1200"
  );
  assert.equal(
    priceModule.getDoctorDisplayPrice(
      doctor,
      { price: "", price_child: "" },
      birthDateYearsAgo(10)
    ),
    "2000"
  );
});
