from playwright.sync_api import sync_playwright

def verify_exam():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        try:
            print("Navigating to Exam Page...")
            page.goto("http://localhost:8080/student/exam")

            # Wait for mock data to load
            print("Waiting for exam title...")
            page.wait_for_selector("#exam-title", timeout=5000)

            # Check title text
            title = page.locator("#exam-title").text_content()
            print(f"Exam Title: {title}")

            # Check first question
            print("Checking first question...")
            page.wait_for_selector("#question-container h4")

            # Click next
            print("Clicking Next...")
            page.click("#btn-next")

            # Wait a bit for transition
            page.wait_for_timeout(1000)

            # Screenshot
            print("Taking screenshot...")
            page.screenshot(path="verification/exam_screenshot.png", full_page=True)

        except Exception as e:
            print(f"Error: {e}")
            page.screenshot(path="verification/error_screenshot.png")
        finally:
            browser.close()

if __name__ == "__main__":
    verify_exam()
