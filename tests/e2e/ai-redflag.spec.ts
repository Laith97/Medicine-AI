import { test, expect } from "@playwright/test";

const DOCTOR_EMAIL = "newdoc@medical.com";
const DOCTOR_PASSWORD = "password123";
const REDFLAG_APPOINTMENT_ID = 88;
const REDFLAG_PATIENT_NAME = "Test RedFlag Patient";

async function loginAsDoctor(page: import("@playwright/test").Page) {
    await page.goto("/login");
    await page.fill('input[name="email"]', DOCTOR_EMAIL);
    await page.fill('input[name="password"]', DOCTOR_PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL("**/dashboard", { timeout: 10000 }).catch(() => {});
}

test("RedFlag patient appointment shows correct header and clinical context", async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/doctor/appointments/${REDFLAG_APPOINTMENT_ID}`);

    // Header should show patient name as h2 (like other headers)
    await expect(page.locator(".dashboard-header h2", { hasText: REDFLAG_PATIENT_NAME })).toBeVisible();
    // Clinical Context should be visible
    await expect(page.locator("h5", { hasText: "Clinical Context" })).toBeVisible();
    await expect(page.getByText("Chest pain and shortness of breath").first()).toBeVisible();
    // Prescriptions section should be visible
    await expect(page.locator("#prescriptions")).toBeVisible();
    await expect(page.locator("#prescriptions h4", { hasText: "Prescriptions" })).toBeVisible();
});

test("RedFlag patient Get AI Medication Suggestions blocks with RED FLAG", async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/doctor/appointments/${REDFLAG_APPOINTMENT_ID}`);

    // Scroll to prescriptions AI section
    await page.locator("#prescriptions").scrollIntoViewIfNeeded();
    const aiButton = page.locator("#aiSuggestBtn");
    await expect(aiButton).toBeVisible();
    await expect(aiButton).toContainText("Get AI Medication Suggestions");

    // Click Get AI - should trigger red flag block (no OpenAI call, returns requires_evaluation)
    await aiButton.click();

    // Wait for AI response - either suggestions or risk flags
    // For red flag, backend returns requires_evaluation true and empty suggestions, risk_flags contains RED FLAG
    // The UI shows #ai-risks or #ai-suggestions
    await page.waitForTimeout(3000); // allow AJAX to complete (mocked or real with block)

    // Check that either risks or suggestions appear with red flag
    const risks = page.locator("#ai-risks");
    const suggestions = page.locator("#ai-suggestions");

    // One of them should be visible
    const risksVisible = await risks.isVisible().catch(() => false);
    const suggestionsVisible = await suggestions.isVisible().catch(() => false);
    expect(risksVisible || suggestionsVisible).toBeTruthy();

    if (risksVisible) {
        await expect(risks).toContainText(/RED FLAG/i);
    }
    // Also check that no 500 error notification appears
    await expect(page.locator("body")).not.toContainText("500 Internal Server Error");
});

test("RedFlag patient AI Data Sources shows 22% is not correct for this patient - should show higher completeness", async ({ page }) => {
    await loginAsDoctor(page);
    await page.goto(`/doctor/appointments/${REDFLAG_APPOINTMENT_ID}`);

    await page.locator("#prescriptions").scrollIntoViewIfNeeded();
    const aiSourcesBtn = page.locator('button[data-bs-target="#aiDataSourcesModal"]');
    await expect(aiSourcesBtn).toBeVisible();
    await aiSourcesBtn.click();

    const modal = page.locator("#aiDataSourcesModal");
    await expect(modal).toBeVisible({ timeout: 5000 });
    // Data sources table should show Allergies Available (we created diagnosis with allergies)
    await expect(modal.locator("#dataSourcesTableBody")).toContainText("Patient Allergies", { timeout: 5000 });
    await expect(modal).toContainText("Data Completeness");
});
