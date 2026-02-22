import os
from playwright.sync_api import sync_playwright

def verify_export():
    if not os.path.exists("verification"):
        os.makedirs("verification")

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Get absolute path to the mock HTML
        file_path = "file://" + os.path.abspath("verification/credential_export_mock.html")
        page.goto(file_path)

        # Screenshot 1: Screen view (masked)
        page.screenshot(path="verification/screen_view.png")
        print("Screen view screenshot saved.")

        # Screenshot 2: Hover view
        page.hover(".secret")
        # Wait a bit for transition
        page.wait_for_timeout(500)
        page.screenshot(path="verification/hover_view.png")
        print("Hover view screenshot saved.")

        # Screenshot 3: Print view
        page.emulate_media(media="print")
        page.screenshot(path="verification/print_view.png")
        print("Print view screenshot saved.")

        browser.close()

if __name__ == "__main__":
    verify_export()
