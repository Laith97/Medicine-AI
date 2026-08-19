import { test, expect } from "@playwright/test";

const DOCTOR_EMAIL = "newdoc@medical.com";
const DOCTOR_PASSWORD = "password123";
const PATIENT_NAME = "newpatient";
const PATIENT_EMAIL = "newpatient@medical.com";

async function loginAsDoctor(page: import("@playwright/test").Page) {
    await page.goto("/login");
    await page.fill('input[name="email"]', DOCTOR_EMAIL);
    await page.fill('input[name="password"]', DOCTOR_PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL("**/dashboard");
}

test("doctor can log in", async ({ page }) => {
    await loginAsDoctor(page);
    await expect(page).toHaveURL(/\/dashboard/);
});

test("patient list shows stats and patient rows", async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto("/doctor/patients");

    await expect(page.locator("h2", { hasText: "My Patients" })).toBeVisible();
    await expect(page.locator(".stats-card")).toHaveCount(3);
    await expect(page.locator("table tbody tr", { hasText: PATIENT_NAME })).toHaveCount(1);
});

test("search filters the patient list", async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto("/doctor/patients");

    const row = page.locator("table tbody tr").filter({ hasText: PATIENT_NAME });
    await expect(row).toBeVisible();

    await page.fill("#filter-search", "nonexistent-patient");
    await page.locator('form[action*="/doctor/patients"] button[type="submit"]').click();

    await expect(page).toHaveURL(/\/doctor\/patients\?.*search=nonexistent-patient/);
    await expect(page.locator("table tbody tr", { hasText: PATIENT_NAME })).toHaveCount(0);
    await expect(page.locator("h5", { hasText: "No patients found" })).toBeVisible();
});

test("view patient details page", async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto("/doctor/patients");

    const row = page.locator("table tbody tr").filter({ hasText: PATIENT_NAME });
    await row.locator('a[title="View Details"]').click();

    await expect(page).toHaveURL(/\/doctor\/patients\/\d+/);
    await expect(page.locator("h4").first()).toHaveText(PATIENT_NAME);
    await expect(page.locator("h2", { hasText: "Patient Details" })).toBeVisible();
    await expect(page.getByText(PATIENT_EMAIL)).toBeVisible();
});

test("patient details page shows appointment history", async ({ page }) => {
    await loginAsDoctor(page);
    await page.locator("#root").count().catch(() => undefined);
    await page.goto("/doctor/patients");

    const row = page.locator("table tbody tr").filter({ hasText: PATIENT_NAME });
    await row.locator('a[title="View Details"]').click();

    await expect(page.locator("h5", { hasText: "Appointments History" })).toBeVisible();
    const appointmentRows = page.locator("table tbody tr");
    await expect(appointmentRows.first()).toBeVisible();
});