
from playwright.sync_api import sync_playwright

def verify_cutter_flow():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context()
        page = context.new_page()

        try:
            # 1. Login as Admin to add material and roll (already done in integration test, but let's be sure or check dashboard)
            page.goto("http://localhost:8000/login.php")
            page.fill("input[name='username']", "cutter")
            page.fill("input[name='password']", "cutter123")
            page.click("button[type='submit']")

            # Check if logged in
            page.wait_for_selector("text=Xush kelibsiz, Kesuvchi!")

            # Go to Cut Page
            page.click("text=Material Kesish")

            # Fill Cut Form
            # Note: We need a valid roll ID. Since we ran integration test, roll ID 1 should have 50m left.
            # We will select the first option in the select box (value 1)
            page.select_option("select[name='roll_id']", index=1)
            page.fill("input[name='customer_name']", "Playwright User")
            page.fill("input[name='length']", "2")

            # Submit
            page.click("button[type='submit']")

            # Check for Success Message
            page.wait_for_selector("text=Bajarildi!")

            # Screenshot
            page.screenshot(path="verification/cutter_success.png")
            print("Cutter flow verified.")

        except Exception as e:
            print(f"Error: {e}")
            page.screenshot(path="verification/error.png")
        finally:
            browser.close()

if __name__ == "__main__":
    verify_cutter_flow()
