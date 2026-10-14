# B2B & B2C Store Plugin — User Guide
**Plugin:** Printcart B2B Store  
**Version:** 1.0  
**Compatible with:** WordPress 5.8+, WooCommerce 6.0+, CMSmart NBDesigner (optional)

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [System Requirements](#2-system-requirements)
3. [Installation](#3-installation)
4. [Admin Dashboard Overview](#4-admin-dashboard-overview)
5. [Managing B2B Stores](#5-managing-b2b-stores)
6. [Managing Customers](#6-managing-customers)
7. [Invitations](#7-invitations)
8. [Custom Quotes](#8-custom-quotes)
9. [Pricing Rules](#9-pricing-rules)
10. [Settings](#10-settings)
11. [B2B Portal (Customer-Facing)](#11-b2b-portal)
12. [B2B Customer Workflows](#12-b2b-customer-workflows)
13. [FAQ](#13-faq)

---

## 1. Introduction

The **B2B & B2C Store** plugin lets you manage two distinct customer groups within a single WooCommerce store:

- **B2C (Retail):** Regular customers shopping at standard list prices
- **B2B (Wholesale):** Business partners and resellers who receive tiered discounts and exclusive pricing

### Key Features

| Feature | Description |
|---------|-------------|
| B2B Store Management | Review and approve/reject B2B partnership applications |
| Tier Pricing | Percentage discounts by customer tier (Bronze, Silver, Gold, custom…) |
| Bulk Discounts | Additional discounts stacked on top of tier when buying in quantity |
| Team Management | Each B2B store can invite and manage multiple team members |
| Custom Quotes | Full quote workflow — request, review, send, accept, convert to order |
| B2B Portal | Dedicated self-service dashboard for B2B customers |
| Admin Notifications | Badge counts on the admin menu for all pending actions |
| Revenue Chart | 30-day B2C vs B2B revenue trend on the dashboard |

---

## 2. System Requirements

- WordPress **5.8** or later
- WooCommerce **6.0** or later
- PHP **7.4** or later
- CMSmart NBDesigner *(optional — only required if selling custom print products)*

---

## 3. Installation

### Step 1 — Upload the plugin
1. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**
2. Select `printcart-b2bstore.zip`
3. Click **Install Now**, then **Activate Plugin**

### Step 2 — Initial setup
After activation, go to **B2B & B2C → Settings** to:
- Assign a page to use as the B2B Portal
- Configure notification emails
- Enable or disable self-registration for B2B applicants

> The plugin automatically creates all required database tables on first activation. No manual database setup is needed.

---

## 4. Admin Dashboard Overview

Go to **B2B & B2C → Overview**

### Summary Cards
| Card | What it shows |
|------|---------------|
| Revenue (30 days) | Total store revenue for the last 30 days |
| Active Orders | Orders currently in processing |
| Pending Actions | Total items requiring admin attention |
| B2B Stores Active | Number of approved B2B partner stores |

### Revenue Trend Chart
- Stacked bar chart covering the **last 30 days**
- Light blue bars = B2C revenue
- Dark blue bars = B2B revenue
- Hover over any bar to see the exact day breakdown

### Other Sections
- **Recent Orders** — last 10 orders with customer, status, and total
- **Recent B2B Stores** — newly registered partner stores
- **Pending Actions** — clickable items linking directly to the relevant admin page
- **Recent Activity** — tier change history and new store applications

### Menu Badge Notifications
The red badge on the **B2B & B2C** menu item shows the total count of:
- Pending B2B store applications
- Pending team invitations
- New (unread) quote requests

---

## 5. Managing B2B Stores

### 5.1 Store List
Go to **B2B & B2C → B2B Stores**

| Status | Meaning |
|--------|---------|
| 🟡 Pending | Application submitted, awaiting review |
| 🟢 Active | Approved and operational |
| 🔴 Rejected | Application declined |
| ⚫ Suspended | Temporarily disabled |

### 5.2 Approving an Application
1. Click the store name (or the **View** button) to open the detail page
2. Review company information: name, address, tax ID, team members
3. Click **Approve** → optionally add an internal note → confirm
   - The system automatically assigns the default discount tier to the account
   - A notification email is sent to the applicant
4. Click **Reject** to decline the application

### 5.3 Editing Store Information
1. Open the store detail page → click **Edit**
2. Update company name, business type, address, or discount tier
3. Click **Save**

### 5.4 Store-Level Discount Tier
Each B2B store can be assigned a **store-wide discount tier** (e.g. 20% off all products). This applies to **all team members** of that store, unless an individual member has a higher personal tier.

---

## 6. Managing Customers

### 6.1 Customer List
Go to **B2B & B2C → Customers**

- Displays both B2C and B2B customers
- Search by name or email
- Filter by tier

### 6.2 Customer Detail Page
Click on a customer's name to view:
- **Stats:** Total spent, total orders, average order value
- **Contact info:** Email, phone, shipping address
- **B2B Store:** The store this customer belongs to (if any)
- **Current tier** and progress bar toward the next tier
- **Tier change history**

### 6.3 Manually Changing a Customer's Tier
1. Open the customer detail page
2. Find the **Tier & Rewards** panel on the right side
3. Select a new tier from the dropdown
4. Click **Save Tier**

> The change is recorded in tier history with the note "Manual override by admin".

---

## 7. Invitations

### 7.1 Overview
Go to **B2B & B2C → Invitations**

Invitations are created by admins or B2B store owners to add members to a team.

### 7.2 Invitation Flow

```
Admin or store owner creates an invitation
            ↓
Email sent to the invited address
            ↓
Recipient clicks Accept or Decline in the email
            ↓
Accept  → Added to team, receives store discount tier
Decline → Invitation cancelled
```

### 7.3 Tier Behavior When Joining a Team
- If the invitee **has no B2B account**: They are upgraded to B2B upon accepting
- If the invitee **has a higher personal tier than the store**: Their personal tier is kept (never downgraded)
- When a member **leaves the team**: Their tier reverts to what it was before joining

---

## 8. Custom Quotes

### 8.1 Quote Workflow

```
Customer submits a quote request (New Request)
            ↓
Admin reviews → adjusts pricing → sends quote (Sent)
            ↓
Customer receives email with Accept / Decline link
            ↓
Accept  → Quote converts to a WooCommerce order automatically
Decline → Quote is closed
```

### 8.2 Processing a New Quote Request
1. Go to **B2B & B2C → Custom Quotes**
2. Click a quote with status **New Request**
3. Review the product list, quantities, and any special requirements
4. Edit line-item prices if needed
5. Click **Save** → **Send to Customer**

> Once you click **Send to Customer**, the customer receives an email containing a one-click Accept/Decline link.

### 8.3 Quote Statuses
| Status | Meaning |
|--------|---------|
| New Request | Submitted by customer, not yet reviewed |
| In Review | Admin is currently reviewing |
| Sent | Quote sent to customer, awaiting response |
| Accepted | Customer accepted the quote |
| Declined | Customer declined |
| Expired | Quote passed its expiry date |

---

## 9. Pricing Rules

### 9.1 B2B Tier Pricing
Go to **B2B & B2C → Pricing Rules → B2B Tier Pricing**

This is the **base discount** applied to all products for customers of that tier:

| Tier | Discount | Applied to |
|------|----------|------------|
| Bronze | 10% | All customers with Bronze tier |
| Silver | 15% | All customers with Silver tier |
| Gold | 20% | All customers with Gold tier |
| *(Custom)* | *(configurable)* | *(any custom tier you create)* |

**Adding a custom tier:**
1. Click **+ Add Tier**
2. Enter a name (e.g. "Premium") and a discount percentage
3. Click **Save**

### 9.2 Quantity-Based Discounts
Additional discounts stacked **on top of** the tier discount when customers buy in volume:

**Example configuration:**
| Minimum Quantity | Extra Discount |
|-----------------|----------------|
| 5 items | +5% |
| 10 items | +10% |
| 20 items | +15% |

**Result:** A Silver tier customer (15%) buying 10 items → 15% + 10% = **25% total discount**

The **"Applies to"** field controls which customers qualify:
- **All B2B Customers** — anyone with a B2B account
- **Specific Tiers** — only selected tiers qualify

### 9.3 How Discounts Are Displayed on Product Pages
When a logged-in B2B customer views a product page:

**Standard products (no print options):**
- Below threshold: `🎁 Buy 5+ items for extra -10% off!`
- At/above threshold: Blue banner — `✅ Bulk discount applied! Tier -15% + Quantity -10% = -25% total`

**Print products (CMSmart NBDesigner):**
- A "Your B2B price" line appears below the product price, reflecting the discounted total including any printing options selected

---

## 10. Settings

Go to **B2B & B2C → Settings**

### General Tab
| Option | Description |
|--------|-------------|
| B2B Portal Page | Select which WordPress page serves as the B2B Portal |
| Allow Self-Registration | Let customers apply for B2B status on their own |
| Auto-approve B2B | Approve applications automatically without admin review |

### Email Tab
Configure the content and subject lines for transactional emails:
- Welcome email when a B2B application is approved
- Rejection notification email
- Team invitation email
- Quote notification email

> **Important:** Email delivery requires a functioning SMTP setup. On a local development environment, emails will not be sent. Install and configure an SMTP plugin (such as WP Mail SMTP) before going live.

---

## 11. B2B Portal

### 11.1 What Is the B2B Portal?
The B2B Portal is a **dedicated full-screen page** for B2B customers. When a B2B account logs in, they are automatically redirected here instead of the standard WooCommerce My Account page.

### 11.2 Accessing the Portal
- Default URL: `yourdomain.com/b2b-portal/`
- B2B customers are redirected automatically after login
- To bypass the redirect (e.g. for testing): append `?pcs_noredirect=1` to any URL

### 11.3 Portal Tabs

| Tab | Function |
|-----|----------|
| Dashboard | Account summary, current tier, order stats |
| Orders | Full order history |
| B2B Store | Information about the customer's B2B store |
| Teams | Manage members, send invitations |
| Request Quote | Submit a new custom quote request |
| My Quotes | View and respond to sent quotes |
| Profile | Update personal info, phone, address, and password |

### 11.4 Teams Feature
- The B2B store owner can **invite members** by email
- Invited users receive an email with Accept/Decline links
- All team members share the **store's discount tier**
- Admins can view and manage all teams under the Invitations menu

---

## 12. B2B Customer Workflows

### Applying to Become a B2B Partner

```
1. Customer visits the store → clicks "B2B Pricing" or "Apply for B2B"
2. Fills in the application: company name, address, tax ID, reason for partnership
3. System sends a confirmation email: "Your application is under review"
4. Admin reviews in B2B Stores → approves or rejects
5. Customer receives an email with the result
6. If approved → next login redirects to the B2B Portal
```

### Placing an Order with B2B Pricing

```
1. Customer logs in as B2B → browses the product catalog
2. Prices are automatically shown with the tier discount applied
3. Add to cart → bulk discounts applied automatically at checkout if thresholds are met
4. Complete checkout through WooCommerce as usual
```

### Requesting a Custom Quote

```
1. Customer opens the Portal → goes to "Request Quote"
2. Adds products, quantities, and any special notes
3. Clicks Submit
4. Admin is notified → reviews, adjusts pricing, sends the quote
5. Customer receives email → clicks Accept → order is created automatically
```

---

## 13. FAQ

**Q: Can B2C customers see B2B prices?**  
A: No. Discounted prices are only visible when logged in with an approved B2B account.

**Q: What happens to a team member's tier when they leave a team?**  
A: Their tier reverts to what it was before joining. If they were a B2C customer before being invited, they return to B2C status.

**Q: Can I create custom tiers beyond Bronze / Silver / Gold?**  
A: Yes. Go to Pricing Rules → B2B Tier Pricing → Add Tier to create any tier with any discount percentage.

**Q: Do tier discounts and quantity discounts stack?**  
A: Yes. For example, a Silver tier customer (15%) buying 10 items (+10%) receives a 25% total discount.

**Q: Is there an expiry date for quotes?**  
A: Yes, this is configurable in Settings. The default is 7 days.

**Q: If I move the plugin to a new server, do I need to reconfigure it?**  
A: Only the Settings page needs to be reviewed (re-select the B2B Portal page). All pricing rules and customer data stored in the database will migrate with a standard database export/import.

**Q: Does the plugin work with themes other than CMSmart?**  
A: Yes. The plugin is independent of the theme and integrates directly with WooCommerce. The CMSmart integration is only needed if you sell products with custom printing options.

**Q: Where do B2B customers reset their password?**  
A: The B2B Portal login screen includes a "Lost your password?" link, which uses WooCommerce's standard password reset flow.

**Q: Can a single user belong to multiple B2B stores?**  
A: No. A user can only be an active member of one B2B store at a time.

**Q: What happens if a B2B store is suspended?**  
A: All team members lose their B2B pricing benefits while the store is suspended. Their tiers revert until the store is reactivated.

---

## Support

If you encounter any issues, please contact the plugin provider with the following information:
- A clear description of the problem
- Screenshots of any error messages
- Your WordPress version, WooCommerce version, and PHP version

---

*This guide applies to plugin version 1.0*
