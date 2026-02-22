from playwright.sync_api import sync_playwright
import os

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)

        page = browser.new_page()

        # Load the local HTML file
        file_path = f"file://{os.getcwd()}/exam-interface.html"
        print(f"Loading {file_path}")
        page.goto(file_path)

        # 1. Mobile View (Closed)
        page.set_viewport_size({"width": 375, "height": 667})
        page.screenshot(path="verification/exam_mobile_closed.png")
        print("Captured Mobile screenshot (Closed)")

        # 2. Mobile View (Open Navigator)
        # Click the toggle button
        page.click('button[data-bs-toggle="offcanvas"]')
        # Wait for animation
        page.wait_for_timeout(500)
        page.screenshot(path="verification/exam_mobile_open.png")
        print("Captured Mobile screenshot (Open Navigator)")

        browser.close()

if __name__ == "__main__":
    run()
