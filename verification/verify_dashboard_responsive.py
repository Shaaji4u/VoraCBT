from playwright.sync_api import sync_playwright
import os

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)

        page = browser.new_page()

        # Load the local HTML file
        file_path = f"file://{os.getcwd()}/admin-dashboard.html"
        print(f"Loading {file_path}")
        page.goto(file_path)

        # 1. Desktop View
        page.set_viewport_size({"width": 1280, "height": 800})
        page.screenshot(path="verification/dashboard_desktop.png")
        print("Captured Desktop screenshot")

        # 2. Tablet View
        page.set_viewport_size({"width": 768, "height": 1024})
        page.screenshot(path="verification/dashboard_tablet.png")
        print("Captured Tablet screenshot")

        # 3. Mobile View (Closed)
        page.set_viewport_size({"width": 375, "height": 667})
        page.screenshot(path="verification/dashboard_mobile_closed.png")
        print("Captured Mobile screenshot (Closed)")

        # 4. Mobile View (Open Menu)
        # Click the toggle button
        page.click('button[data-bs-toggle="offcanvas"]')
        # Wait for animation
        page.wait_for_timeout(500)
        page.screenshot(path="verification/dashboard_mobile_open.png")
        print("Captured Mobile screenshot (Open Menu)")

        # 5. Dark Mode View (Desktop)
        page.set_viewport_size({"width": 1280, "height": 800})
        # Inject the data-bs-theme="dark" attribute
        page.evaluate("document.documentElement.setAttribute('data-bs-theme', 'dark')")
        # Allow a moment for styles to apply if needed (though usually instant)
        page.wait_for_timeout(500)
        page.screenshot(path="verification/dashboard_desktop_dark.png")
        print("Captured Desktop Dark Mode screenshot")

        browser.close()

if __name__ == "__main__":
    run()
