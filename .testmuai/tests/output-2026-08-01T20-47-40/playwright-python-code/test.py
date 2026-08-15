import os
import testmu
from testmu import expect, var, set_var
from playwright.async_api import Page

testmu.configure(
    build="2e4c394a-29b5-4a22-89fd-6431d2faa593",
    name="Project Debugging and Vulnerability Assessment",
    tc_id="TC-2",
    network=os.getenv("NETWORK", "false").lower() == "true",
    variables={"answer": "Dashboard | Workflow Designer | Approval Templates | Approval Requests | Pending Approvals | My Approvals | Approval History | Delegations | Escalations | Notifications | Audit Trail | Reports | Settings | Approval History | Every completed approval request \u2014 approved, rejected or cancelled. | APR-00010\n\t\nPurchase Request \u2014 PR-0003\n\t\nPurchase Request Approval\n\t\nApproved\n\t\n\t\nJul 26, 2026 14:57:59\n\t\nJul 26, 2026 14:59:03\n\t\n1 minute\n\t\nView | APR-00001\n\t\nLeave Request \u2014 Agnes Nduta\n\t\nLeave Request Approval\n\t\nApproved\n\t\n\t\nJul 26, 2026 14:57:59\n\t\nJul 26, 2026 14:58:42\n\t\n43 seconds\n\t\nView | APR-00009\n\t\nPurchase Request \u2014 PR-0001\n\t\nPurchase Request Approval\n\t\nApproved\n\t\n\t\nJul 26, 2026 14:57:59\n\t\nJul 26, 2026 14:58:42\n\t\n43 seconds\n\t\nView | Showing 1 to 3 of 3 results\nPer page\n5\n10\n25\n50\nAll"},
    default_action_timeout_ms=10000,
    default_navigation_timeout_ms=30000,
    kane_run_v4=True,
)

@testmu.test
async def test(page: Page):
    async with testmu.step('Navigate to http://truck-manager.test/admin/workflow-approvals/dashboard', instruction_id='7a6c547e-cffb-45c2-8167-db9764834be6'):
        await page.goto("http://truck-manager.test/admin/workflow-approvals/dashboard")
    
    async with testmu.step('Clicking Workflow Designer link in the Workflow & Approvals menu', instruction_id='9da341c3-11c9-4f52-926a-9edac34dc0f7'):
        _loc_1 = page.locator("internal:role=link[name=\"Workflow Designer\"i]")
        
        await _loc_1.click()
    
    async with testmu.step('Clicking Approval Templates link in the left submenu', instruction_id='91bfe5d2-8eb6-4fb0-a852-70302d624fbb'):
        _loc_2 = page.locator("internal:role=link[name=\"Approval Templates\"i]")
        
        await _loc_2.click()
    
    async with testmu.step('Clicking Approval Requests link', instruction_id='6341e7fe-fe16-4617-81cd-5e00cbcde6fc'):
        _loc_3 = page.locator("internal:role=link[name=\"Approval Requests\"i]")
        
        await _loc_3.click()
    
    async with testmu.step('Clicking Pending Approvals', instruction_id='c406037d-7c69-49c7-808f-1cd9add538f4'):
        _loc_4 = page.locator("internal:role=link[name=\"Pending Approvals\"i]")
        
        await _loc_4.click()
    
    async with testmu.step('Clicking Approval History link', instruction_id='766feca3-3f40-47a3-93c8-a3495a89392e'):
        _loc_5 = page.locator("internal:role=link[name=\"Approval History\"i]")
        
        await _loc_5.click()
    
    async with testmu.step('Visible text summarizing the Workflow & Approvals module entry points plus the Approval History page description and listed completed requests.'):
        set_var('answer', await testmu.textual_analyzer(page, wrapped_js="(els) => {\n  const __m = {26: 0, 28: 1, 30: 2, 32: 3, 34: 4, 36: 5, 38: 6, 40: 7, 42: 8, 44: 9, 46: 10, 48: 11, 50: 12, 52: 13, 53: 14, 72: 15, 82: 16, 92: 17, 102: 18};\n  const el = (i) => els[__m[i]];\n  const __v = ([el(26).innerText, el(28).innerText, el(30).innerText, el(32).innerText, el(34).innerText, el(36).innerText, el(38).innerText, el(40).innerText, el(42).innerText, el(44).innerText, el(46).innerText, el(48).innerText, el(50).innerText, el(52).innerText, el(53).innerText, el(72).innerText, el(82).innerText, el(92).innerText, el(102).innerText].join(' | '));\n  return (typeof __v === 'boolean' ? String(__v) : __v);\n}", locators=['internal:role=main >> internal:role=link[name="Dashboard"i]', 'internal:role=link[name="Workflow Designer"i]', 'internal:role=link[name="Approval Templates"i]', 'internal:role=link[name="Approval Requests"i]', 'internal:role=link[name="Pending Approvals"i]', 'internal:role=link[name="My Approvals"i]', 'internal:role=link[name="Approval History"i]', 'internal:role=link[name="Delegations"i]', 'internal:role=link[name="Escalations"i]', 'internal:role=main >> internal:role=link[name="Notifications"i]', 'internal:role=link[name="Audit Trail"i]', 'internal:role=main >> internal:role=link[name="Reports"i]', 'internal:role=main >> internal:role=link[name="Settings"i]', 'h1 >> internal:has-text=/^Approval History$/', 'internal:text="Every completed approval"i', 'internal:role=row[name="APR-00010 Purchase Request —"i]', 'internal:role=row[name="APR-00001 Leave Request —"i]', 'internal:role=row[name="APR-00009 Purchase Request —"i]', 'internal:role=navigation[name="Pagination navigation"i]'], query='Workflow & Approvals module review: Functionality observed: (1) Dashboard provides module entry points and summary/overview navigation. (2) Workflow Designer / Workflow Definitions manages approval workflow definitions, including workflow names, target modules, status, and design actions. (3) Approval Templates provides reusable approval patterns/templates with node counts and actions such as Use Template. (4) Approval Requests lists submitted approval requests tied to modules/workflows and acts as the intake stage before approver worklists. (5) Pending Approvals is the active approver queue for outstanding actions. (6) Approval History records completed approval requests with reference number, title, workflow, status, submitted timestamp, and completed timestamp. Relations between modules: workflow definitions are the core configuration; templates reuse or package workflow structures; requests instantiate a workflow/template for a business record; pending approvals represents in-flight approver tasks generated from submitted requests; approval history is the terminal state after approval/rejection/cancellation; dashboard links and summarizes the overall area. Flagged vulnerabilities / issues: no obvious security exploit was directly exposed in the visited UI, but there are notable quality/debug risks: navigation clicks sometimes reported weak targeting/no focused element, suggesting flaky UI interaction behavior; Pending Approvals showed an empty-state queue (No approval actions), which may indicate missing seed data, broken routing into approver inboxes, or a workflow handoff gap; several areas rely heavily on list views without obvious validation/audit cues on-screen, so authorization, audit logging, and state-transition controls should be verified further; completed history currently shows approved items only in the sampled view, so rejection/cancellation paths and access control should be tested separately. Overall assessment: the module structure is coherent and the request lifecycle from design/template to request, pending worklist, and history is visible, but the project needs additional verification around inbox population, permissions, and state-transition robustness.', expected_value='', needs_unit_conversion=False, operator='contains', transforms=['strip'], condition='report of functionality, relation of modules, and flagged vulnerabilities'))


if __name__ == "__main__":
    testmu.run(test)