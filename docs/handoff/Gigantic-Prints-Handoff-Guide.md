# Gigantic Prints online store - Handoff Guide

Version 1.2 · prepared for **Hoyle Fabrio, SignEdge Graphics** by **Printcart, a division of Netbase JSC**.
Store: giganticprints.com (staging address: giganticprints.printcart.com). Hosting is managed by Printcart.

<!-- Internal notes are HTML comments; build_pdf.py strips them. Screenshot list: capture/shots.json (id = file name). -->

## About Netbase JSC and Printcart

<div class="profile">
<div class="pf-head"><img class="pf-logo-nb" src="assets/netbase-logo.png" alt="Netbase JSC"></div>
<p class="pf-lead"><strong>Netbase JSC</strong> is a Hanoi-based technology company that has designed, built and run e-commerce and web-to-print systems since 2012. It started with e-commerce products, added consulting and outsourcing services in 2016, and today delivers remotely, in English, to clients in the United States, Europe and Asia-Pacific. Contracts and invoices are issued by Netbase JSC.</p>
<div class="pf-stats">
<div><b>2012</b><span>founded in Hanoi, Vietnam</span></div>
<div><b>500+</b><span>clients served</span></div>
<div><b>80</b><span>countries with clients</span></div>
<div><b>50+</b><span>custom web-to-print platforms delivered</span></div>
</div>
<div class="pf-cols">
<div>
<h4>How Netbase delivers</h4>
<ul>
<li>Delivery teams of 3 to 30 people: business analysts, project managers, solution architects, developers, QA and UI/UX designers.</li>
<li>Work usually starts one to two weeks after discovery; most projects are fixed-price after discovery.</li>
<li>Commerce platforms: WooCommerce, Shopify, Magento 2, Laravel and headless commerce; cloud on AWS, Google Cloud and Cloudflare.</li>
<li>ISO 27001 certified and SOC 2 Type II attested; contributors work under NDA, and NDAs and DPAs are available.</li>
<li>You own the intellectual property created in custom development.</li>
</ul>
</div>
<div>
<h4>Results for print businesses</h4>
<ul>
<li><strong>4over4</strong> (USA): revenue +82% within six months, conversion +48%, average order value +35%.</li>
<li><strong>USticker</strong>: +40% conversion for design-tool users, online-order revenue +15%.</li>
<li><strong>Indieprint</strong> (Melbourne): design errors -28%.</li>
<li><strong>Geo-Tek IT Solutions</strong> (Cyprus): revenue +36% in the first quarter.</li>
</ul>
<p class="pf-note">Recognised as a Top Software Development Company (GoodFirms) and listed among top web developers by Clutch and Wadline.</p>
</div>
</div>
<h4>Business divisions</h4>
<div class="pf-divs">
<div class="pf-div pf-hl"><b>Printcart</b><span>Web-to-print and print-on-demand platform. 2,000+ stores.</span></div>
<div class="pf-div"><b>CMSmart</b><span>E-commerce stores and product customizers. 7,000+ stores launched.</span></div>
<div class="pf-div"><b>Cloodo</b><span>AI-powered digital workplace: CRM, HRM, cloud ERP.</span></div>
<div class="pf-div"><b>Poslor</b><span>Operating system for real estate: listings, leads, deals.</span></div>
<div class="pf-div"><b>Storelly</b><span>D2C SaaS platform for direct-selling brands.</span></div>
</div>
</div>

<div class="profile pf-pc">
<div class="pf-head"><img class="pf-logo-pc" src="assets/printcart-logo.png" alt="Printcart"><span class="pf-tag">a division of Netbase JSC</span></div>
<p class="pf-lead"><strong>Printcart</strong> is the web-to-print division of Netbase JSC. The online designer, the price matrix and the print-ready file output in your store run on the Printcart platform, which is maintained and improved by a dedicated product team.</p>
<div class="pf-stats">
<div><b>2,000+</b><span>stores worldwide</span></div>
<div><b>10 years</b><span>of service</span></div>
<div><b>3</b><span>platforms: WooCommerce, Shopify, Wix</span></div>
<div><b>12,400+</b><span>vector cliparts</span></div>
</div>
<div class="pf-cols">
<div>
<h4>What the platform gives a print shop</h4>
<ul>
<li>Online designer with print sides, bleed and safe zones, and a print-ready PDF for every order.</li>
<li>Dynamic price matrix: prices by size, material and options, as your banners are priced by the square foot.</li>
<li>AI print tools billed per use: background removal, image upscaling and print-file preflight.</li>
<li>REST API, webhooks and JavaScript SDKs for custom storefronts and integrations.</li>
</ul>
</div>
<div>
<h4>What it means for Gigantic Prints</h4>
<ul>
<li>Your store is built on a platform already used by thousands of print stores, not on one-off code.</li>
<li>Platform updates and security fixes continue to reach your store.</li>
<li>Custom work for your business, such as the B2B portal and the upload-first flow, is delivered by Netbase engineering and documented in this guide.</li>
<li>One accountable company for platform, custom work, hosting and support.</li>
</ul>
</div>
</div>
</div>

<div class="pf-contact">
<b>Netbase Business Application And Solution Joint Stock Company (Netbase JSC)</b><br>
No. 18, Lane 445/46 Lac Long Quan Street, Tay Ho Ward, Hanoi City, Vietnam<br>
netbasejsc.com · printcart.com · project@netbasejsc.com · ceo@printcart.com · WhatsApp +84 937 869 689<br>
Chairman and CEO: David Nguyen Quang Huy · Payment to Netbase JSC by company bank transfer or PayPal Business
</div>

<div style="page-break-after: always"></div>

## How to read this guide

- **Part 1** tells you what was delivered and what comes next.
- **Part 2** is for your customers and your sales staff: what a shopper sees and does, step by step.
- **Part 3** is for you and your administrators: how to run the store behind the scenes.
- **Part 4** is the checklist for the day the store goes live on giganticprints.com.
- **Part 5** lists the questions we need you to answer.
- **Part 6** describes how support and care work after go-live.
- **Appendix A** is an acceptance checklist you can tick while you try the store.

Words in **bold** are the exact labels you will see on screen. Red outlines and numbers in the screenshots point at the thing to look at. Lines that start with **Check:** tell you what you should see if everything works. The screenshots show sample companies (Demo Signs Co, Demo Reseller Co) and sample people; they are not real customers.

---

## 1. Overview

### 1.1 What the store is

One store for everybody: visitors, retail customers (B2C) and business clients (B2B). It follows the ordering flow you described from Signs365:

**Product -> upload your art (or design online) -> instant preview at the real size -> options and live price -> cart -> checkout.**

Nobody needs an account to start designing. An account is needed when the order goes to checkout.

### 1.2 What was delivered, against your requirement list

| # | Your requirement | What you get |
|---|---|---|
| 1 | One site for visitors, B2C and B2B; Product > Upload/Design (no login) > Cart (login or create account) > Checkout | Delivered. Designing and uploading work without login. Registration is open on **My Account**; checkout asks guests to log in or register. Checkout completes with **Pay by invoice** (see 2.9). |
| 2 | Price changes live with size and options; B2B sees the discounted price | Delivered. The price in the designer and on the product page follows size, material and options. Business clients see their own discounted price in the product page, designer and cart (the discount is applied once). |
| 3 | A separate account per company; repeat 5%, B2B 10%, reseller 20% | Delivered. Tiers **Returning customer (5%)** (a customer moves there automatically after the second order), **B2B (10%)** and **Reseller (20%)**; every company has its own portal, team, Image Zone and templates. |
| 6, 17 | Customers mainly upload art; the designer is optional | Delivered. The designer opens on **Photos** (upload first); the art is placed on the print area by itself. **Upload design** and **Custom design** buttons on the product page. |
| 17-18 | Preview of the art at the real size; size taken from the uploaded file; change size on the same screen; center or stretch | Delivered. The size is read from the file (pixels and DPI). **Stretch to fit**, **Fit (keep ratio)** and **Center**, width and height boxes, and the price updates at once. A local preview appears about a second after you choose a file. |
| 20 | Large files (20-200 MB and more), PDF, AI, PNG, JPG; resolution check | Delivered. Files up to **200 MB**; PNG, JPG, SVG, PDF, AI, EPS, TIFF. A **low resolution** warning appears when the art is below 100 dpi at the chosen size. Add to Cart is blocked while the print area is empty. |
| 7, 15 | Header and category bar in Signs365 order | Delivered. **Banner, Rigid, Adhesive, Handheld, Magnet, Apparel, Misc**. A category without products shows **Coming soon**. |
| 11-14, 26 | Products, options and prices from your price list (banner tiers, pole pocket, rope, acrylic minimum, magnets, apparel by linear inch, rush +100%) | Delivered and checked against your price list of 2026-06-18. Prices are shown in US format ($6.63), catalog pages show **From $...**. Pole pocket and rope rates have their own admin screen (see 3.4). |
| 16 | Combined products (one Magnet product, banner weights as options) | Delivered for Banner (13oz, 15oz, 18oz single and double sided) and Magnet. Other families keep the product structure you approved. |
| 22 | Register as B2C, ask for B2B, administrator approves, B2B profile | Delivered. **Apply for B2B** form, approval in the admin, company portal with team and invitations. |
| 23 | Private products per client; private template library; reorder in one click with the old art | Delivered. Products assigned to one company are hidden from everyone else; company **Templates** (save, rename, clone, delete, start an order); **Reorder** keeps the options and the original art. |
| 24 | Image Zone | Delivered as the company's private image library in the company portal and in the designer's Photos tab. |
| 9 | Footer with contact, Terms | Delivered. Footer links open **Terms of Service**, **Privacy**, **Refund Policy**, **Accessibility** and **Track your order**. The four legal pages are drafts for you to review (see Part 5). |
| - | Print-ready files | Delivered. In the admin you download PNG, PDF (without watermark), SVG and JPG for every order (see 3.8). |
| - | Security for accounts | Business invitations are tied to the invited e-mail; quote accept/decline needs a signed-in click; five failed logins lock the login name for 15 minutes; guest checkout is off; user names are not exposed. |

### 1.3 What is next

These items from your list are **not part of this release**. They are good candidates for the next phase:

- **Item list** on the left of the order screen (several items with their own art in one order, quantity per item, color matching and contour-cut buttons).
- **Grommet positions** all sides / top / bottom / left / right / custom (today: **No grommets**, **Grommets - 4 corners only**, **Grommets - Every 2 ft**), and the **Webbing mesh** option.
- **Super Admin Custom Order Form** for orders that come by phone or e-mail (planned as Phase 2). The quote module can be reused for it.
- **Chat widget, FAQs and how-to videos** in the header and footer.
- **Other payment methods, accounting export (for example QuickBooks) and Apple Pay**: separate quotes, planned together with you.
- **Team order approvals** (Phase 2 idea): a team member's order waits for the company owner's approval before it goes to production.
- Fine tuning of text, images, About us, blog and gallery content with your material.

---

## 2. Shopper guide (what your customers see)

### 2.1 Browse and find a product

1. Open the home page. Under the header you see the category bar: **Banner, Rigid, Adhesive, Handheld, Magnet, Apparel, Misc**.
2. Click a category. Each product shows its starting price (**From $...**) and a **Start Design** button.

![01 Home page with the category bar](images/01-home-menu.png)

![02 Category page with "From" prices and Start Design buttons](images/02-category-from-price.png)

**Check:** prices are shown as $6.63 (dollar sign first, point for decimals). A category without products shows **Coming soon**.

### 2.2 Choose a product and a size

1. Open a product, for example **Banner**. The page shows photos, the description and the configuration panel with the **Total price**.
2. Material (13oz, 15oz, 18oz, single or double sided) is an option of the same product, not a separate product.
3. Press **Start Design**, or use **Upload design** / **Custom design** at the bottom of the product page.

![03 Product page](images/03-product-page.png)

![04 Options and total price on the product page](images/04-product-options-price.png)

![05 Upload design and Custom design buttons](images/05-product-upload-buttons.png)

The designer has four tabs on the left: **Photos** (upload; the designer opens here), **Printing** (all options of the product), **Text** and **Layers**. The live price and the **Add to Cart** button are always on the right.

![06 The designer: Photos tab first, Printing tab for the options, live price on the right](images/06-designer-start.png)

### 2.3 Upload your art and see the preview at once

1. In the designer stay on the **Photos** tab (it is the first tab).
2. Drop your file on the box **Click or drop images here**, or click it and choose the file.
   Accepted file types and the size limit are written under the box: **png, jpg, svg, pdf, ai, eps, tiff** and **Max file size: 200 MB**.
3. About a second after you choose the file a local preview shows the pixel size, the size in inches at the file's resolution and the file size, while a progress bar shows the upload.
4. When the upload is done the art is placed on the print area automatically and the product takes the size of your file (for example a 3600 x 2400 pixel file at 150 dpi becomes 24 x 16 in).
5. The art bar above the print area shows **Actual size: 24 x 16 in**, and the rulers around the print area show inches.

![10 Photos tab: drop zone, accepted types, 200 MB limit](images/10-upload-zone.png)

![18 Local preview and progress right after choosing a big file](images/18-upload-instant-preview.png)

![11 Art placed on the print area, art bar with actual size](images/11-upload-art-placed.png)

**Check:** the preview shows the art on the print area with the rulers in inches, and the price on the right has changed to the price for that size. Large files take longer; the progress bar stays visible until the art appears.

### 2.4 Center, stretch or fit, and change the size

The art bar sits above the print area:

| Button | What it does |
|---|---|
| **Stretch to fit** | The art fills the whole print area exactly. If the proportions differ, the art is stretched. |
| **Fit (keep ratio)** | The whole art is visible inside the print area and keeps its proportions. |
| **Center** | The art keeps its real size and sits in the middle. |

To change the size, type a new number in **W** (width) or **H** (height). With **Keep proportions** ticked, the other number follows. Untick it to enter both. The unit is **in**. The price updates as soon as the size changes.

![12 Stretch to fit](images/12-art-stretch.png)

![13 Fit (keep ratio)](images/13-art-fit.png)

![14 Center](images/14-art-center.png)

![15 Changing the size on the same screen, with the live price](images/15-size-edit-live-price.png)

### 2.5 Resolution warning and empty print area

- If your file would print below 100 dpi at the chosen size you see: **Low resolution: about N dpi at W x H in. The print may look soft. Use a larger file or reduce the print size.** You can still continue; the warning disappears when the size is reduced or a larger file is used.
- If the print area is empty and you press **Add to Cart**, the store shows **Add your artwork first: upload a file or create a design, then press Add to Cart.** Nothing is added to the cart.

![16 Low resolution warning](images/16-low-dpi-warning.png)

![17 Add to Cart is blocked while the print area is empty](images/17-empty-canvas-blocked.png)

### 2.6 Options and live price

1. Open the **Printing** tab. Every option shows its label, the chosen value and the price effect (**Free** or an amount).
2. Banner options: **Grommets**, **Pole Pocket** (**No pole pocket**, **Top only**, **Bottom only**, **Top & Bottom**), **Welding / Hemming**, **Rope Reinforcement** (**No rope**, **Add rope**), **Wind Slits**, **Rush Production (24-hour turnaround)** and **Print-Sides** (the material: 13oz, 15oz, 18oz single or double sided).
3. Under the material choices the size field shows **Width** and **Height** with plus and minus buttons, in inches (banner: width 1 to 60 in, height 1 to 1200 in). When art is uploaded these boxes are filled from the file.
4. Pole pocket and rope are charged per linear foot of the size you chose (rates in 3.4); **Wind Slits** shows its flat charge in its label. The price summary shows **Base price**, **Options**, **Quantity** and **Your price**.
5. When everything is right press **Add to Cart**.

![07 Type the size and choose options: the price follows](images/07-designer-options-live-price.png)

![08 The material is an option of the same product](images/08-designer-material-choice.png)

**Check:** change the size or an option and watch **Your price** change before you press Add to Cart. Business clients see their discounted price here.

### 2.7 Design online (optional)

You do not have to upload anything. The tabs on the left (**Photos, Printing, Text, Layers**) let a customer add text, images and shapes, or edit the uploaded art. Designs can be saved to finish later.

![20 Designer tools in the left column](images/20-designer-text-tools.png)

### 2.8 Register and log in

1. Click **Log in** in the header (also available inside the designer).
2. On **My Account** use the login form, or the **Register** form to create an account with an e-mail address and a password.
3. A customer can design and upload without logging in; the store asks for login or registration when the order goes to checkout.

![22 My Account: login and register](images/22-account-login-register.png)

![21 Log in from inside the designer](images/21-login-in-designer.png)

### 2.9 Cart, checkout and order confirmation

1. After **Add to Cart** the cart page shows each item with the preview of the art, size, options and price.
2. Click **Proceed to Checkout**. A guest is asked to log in or register first.
3. Check **Contact information**, **Shipping address** and **Billing address**.
4. Under **Shipping options** choose **Pickup - Fort Pierce, FL** (free) or **Ground shipping - quoted** (no charge at checkout; the shipping cost is quoted to you).
5. Under **Payment options** choose **Pay by invoice**: you place the order now and receive a PayPal invoice before production starts.
6. Press **Place Order**. The page **Thank you. Your order has been received.** shows the order number, total and payment method. A confirmation e-mail follows.

![30 The cart](images/30-cart.png)

![30b Cart totals, shipping choice and Proceed to Checkout](images/30b-cart-totals.png)

![32 A guest is asked to log in or register](images/32-checkout-guest-login.png)

![31 Checkout](images/31-checkout.png)

![33 Shipping options and Pay by invoice](images/33-checkout-shipping-payment.png)

![34 Order received](images/34-order-received.png)

**Check:** the order total equals the price shown in the designer plus the chosen shipping (no charge for the two shipping options). The order appears in **My Account > Orders**.

To follow an order without logging in, use **Track order** in the footer: enter the order number and the billing e-mail.

![35 Track your order](images/35-track-order.png)

### 2.10 My Account: orders, reorder, quotes

A retail customer's **My Account** page has the menu **Dashboard, Overview, Orders, Downloads, Addresses, Account details, My designs, Log out** and **Apply for B2B**. **Orders** and **Overview** open the portal pages **Orders & Invoices** and the overview with **Request quote**, **My quotes**, **My Profile**.

- **Orders** (**Orders & Invoices**): all orders with status (**In production**, **Shipped**, **Delivered**), a **View** link for the invoice and the **Reorder** button. **Reorder** puts the same items, options and the original art into a new cart; you review it and check out. The same button is on the order confirmation page.
- **Request quote** and **My quotes**: ask for a price on a special job; when you receive a quote you can **Accept** or **Decline** it while you are signed in.
- **Returning customer:** after a customer's second order the account moves to **Returning customer (5%)** and the 5% discount is applied to the following carts.

![40 My Account menu](images/40-my-account-dashboard.png)

![41 Orders & Invoices with the Reorder button](images/41-my-account-orders.png)

![42 My designs](images/42-my-designs.png)

![44 Request quote](images/44-request-quote.png)

![45 My quotes](images/45-my-quotes.png)

### 2.11 Ask to become a B2B client

1. Log in, open **My Account** and click **Apply for B2B**.
2. Fill in the form: **Company name**, **Business email**, **Contact name**, **Phone**, **Industry**, expected monthly volume and a short description of the company.
3. Send it. The store tells you the application is under review.
4. The page also shows the three tiers (**Returning customer (5%)**, **B2B (10%)**, **Reseller (20%)**); your administrator assigns the tier after approval. When your application is approved you receive an e-mail. From your next login you land in the company portal, and the company discount (for example 10%) is applied automatically in the product page, designer and cart.

![43 Apply for B2B](images/43-apply-b2b.png)

### 2.12 The company portal

After approval a B2B client works in the company portal (**B2B Portal** in the home page header, or the address /b2b-portal/). It has a left menu:

- **Overview**: your tier and discount, orders, total spent and savings.
- **Orders**: all orders of the company with **Reorder**.
- **B2B Store**: the company profile.
- **Teams**: invite colleagues by e-mail. An invited person must register with that same e-mail address; they then share the company discount and the company's Image Zone. The page shows the seats used (for example 2 / 5) and the role of each member (**Admin**, **Member**).
- **Image Zone**: the company's own library of artwork (logos, photos). Press **Upload** (PNG, JPG, TIF, PDF, AI or EPS, up to 100 MB per file). Each card shows the file name, pixels, DPI, the print size at that DPI, file size, who uploaded it, and a **Delete** button. **Use in the designer** opens the designer; the same images appear in the **Photos** tab of the designer for company members, where one click places an image. The files are private to your company.
- **Templates**: the company's private template library (see 2.13).
- **Request quote / My quotes** and **My Account** (Dashboard, Orders, My Profile).

![50 Company portal overview](images/50-portal-overview.png)

![51 Teams: members and invitations](images/51-portal-team.png)

![52 B2B Store: company profile](images/52-portal-store.png)

![53 Image Zone](images/53-portal-image-zone.png)

![58 The Image Zone in the Photos tab of the designer](images/58-designer-image-zone.png)

A team member who joined through the invitation shares the company discount: the designer shows it on the price (line **B2B · Team · 10% off**), see 2.15. Resellers get their 20% tier in the product page, designer and cart in the same way.

### 2.13 Company templates and reorder

**Templates.** In the designer a company member sees a **Templates** bar with **Save as company template** and a list **Load template...**.

1. Design or upload the art for a product, then press **Save as company template** and type a name.
2. Templates belong to the product they were made for. To use one, choose it in **Load template...** (the art on the canvas is replaced after you confirm), or open the portal page **Templates** and press **Start order** on a card: the designer opens with that template loaded.
3. On the **Templates** page each card also has **Rename**, **Clone** and **Delete**.
4. Other companies and the public never see your templates. The administrator can also hide the public templates for your company.

![54 Templates: Start order, Rename, Clone, Delete](images/54-portal-templates.png)

![57 Start order from a template](images/57-designer-company-templates.png)

**Reorder.** In the portal page **Orders** press **Reorder** next to an order: the same product, options and original art are put into a new cart. Prices are calculated again with today's price list and your discount.

![59b Reorder in the company portal](images/59b-portal-reorder.png)

### 2.14 Private products

Products that Printcart assigns to your company in the admin appear in the shop and in search only when a member of your company is logged in. For everybody else they do not exist: the product address shows "page not found".

![55 A product assigned to the company, seen by a member](images/55-portal-private-products.png)

![59 The same product for a visitor](images/59-private-product-visitor.png)

### 2.15 Prices for B2B clients

1. Log in with a company account and open the designer, product page or cart.
2. The price shown is already reduced by the company tier (for example 10% for B2B, 20% for Reseller). It is reduced once, never twice.

![56 B2B price in the designer](images/56-designer-b2b-price.png)

![56b A team member sees the company discount in the designer](images/56b-designer-staff-price.png)

**Check:** the same 24 x 16 in banner costs less for a company than for a retail customer, and the cart shows the same amount as the designer.

### 2.16 Footer pages

The footer links to **Terms**, **Privacy**, **Refund policy**, **Accessibility** and **Track order**. The four legal pages are drafts: a banner on each page tells you to review the wording before go-live (Part 5, question 5).

![36 A footer page with the draft banner](images/36-footer-legal.png)

---

## 3. Administrator guide (how to run the store)

### 3.1 Sign in and find your way

1. Open **giganticprints.com/wp-admin** and sign in with your administrator account.
2. The menu on the left is the whole store:
   - **Products** and **WooCommerce** (orders, customers, coupons, settings, **Banner extras pricing**),
   - **PC Designer** (the designer, **Printing Options** with sizes, options and prices),
   - **B2B & B2C** (companies, tiers, quotes),
   - **Pages**, **Posts**, **Media**, **Appearance** (menus), **Users**, **Settings**.

![70 The admin menu](images/70-admin-dashboard-menu.png)

### 3.2 Products and combined products

A **combined product** is one product that contains its variants as options: for example **Banner** holds 13oz, 15oz and 18oz, single and double sided, and all sizes. The customer picks them on one screen.

**See and edit products**

1. Open **Products > All Products**.
2. Click a product name to edit it: title, description, product image and gallery, **Product categories**.
3. In the box **PCD options** the tab **Printing Option** holds the checkbox **Enable Printing option** and the link **Edit option**, which opens the option set of this product (see 3.3). The box **Setting PCDesigner** holds **Enable Design**, **Enable Upload Design** and **Enable Upload Without Design**.
4. Click **Update** to save.

![71 Products > All Products](images/71-admin-products-list.png)

![72 Edit product: the PCD options box](images/72-admin-product-edit.png)

![77 Setting PCDesigner](images/77-admin-product-designer-settings.png)

> The **Price** column of the products list shows the same **From $...** price as the shop catalog. It is updated when you save an option set (3.3).

**Add a new product**

1. **Products > Add new product**. Give it a title, description, image, and tick its category.
2. Open **PC Designer > Printing Options > Add new**, type a title, choose **Apply for: Products** and select the new product on the right, then build the fields (3.3) and press **Publish**.
3. In the product, enable the printing option and the design settings you need, and **Publish**.

### 3.3 Option sets and the price table

**PC Designer > Printing Options** lists one option set per product (or per category). Click a title, or **Edit**, to open **Edit Options**. Do not delete an option set that is in use.

![73 PC Designer > Printing Options](images/73-admin-printing-options-list.png)

![74 Edit Options: fields of the Banner option set](images/74-admin-option-set.png)

The page has:

- **Publish** box on the right (priority, **Update** button) and **Apply for** (the product or the categories the set belongs to).
- **Printing fields**: the building blocks (for example **Size**, **Custom dimension**, **DPI**, **Rounded corners**).
- **Printing options**: the list of fields the customer sees, in order: for Banner **Grommets**, **Pole Pocket**, **Welding / Hemming**, **Rope Reinforcement**, **Wind Slits**, **Rush Production**, **Print-Sides** and one size field per material (**13oz Single-Sided**, **15oz Single-Sided**, **18oz Single-Sided**, **18oz Double-Sided**). Click the arrow at the right of a field to open it.

### 3.4 How to change a price

**A price per area (the price table of a size field)**

Banner, vinyl and similar products are priced by area. The price table lives in the size field of the option set.

1. **PC Designer > Printing Options**, open the set of the product (for Banner: **13oz Vinyl Banner (Single-Sided)**).
2. In **Printing options** find the size field of the material, for example **13oz Single-Sided**, and click the small arrow at the right of its row to open it.
3. Open its tab **Online design**. You see:
   - **Dimension range** (Min, Max, Step and Default value of **Width** and **Height**, in inches),
   - **Enable measure price base on design area**: **Yes**,
   - **Price base on design area**: the price table with the columns **Measurement Range (From / To)** and **Price per Unit ($/in2)**.
4. Change the **Price per Unit** of the row you want, or change the **From** / **To** of the ranges. **Add Rule** adds a row; to remove rows tick them and press **Delete Selected**.
5. Press **Update** (top right of the page).

![75 A size field, tab Online design: limits and the price table](images/75-admin-price-matrix.png)

**Units: the table works in square inches.**

- **Price per Unit** is the price for **one square inch**: price per square foot divided by 144. Example: $6.63 per sq ft is 6.63 / 144 = 0.046042 per sq in.
- **From** and **To** are areas in square inches: square feet x 144. Example: 1000 sq ft = 144000 sq in, so the first range ends at 143999 and the next one starts at 144000.
- The last range has an empty **To** (it applies to everything above its start).

**Check after every change:** open the product in the shop, set a size that falls in the changed range and compare the price on screen with your price list (for example 24 x 16 in = 2.667 sq ft x your price per sq ft). Test the smallest range, the largest range and one size on each side of a range boundary.

**Pole pocket and rope: Banner extras pricing**

These two add-ons are charged per linear foot of the size the customer chooses, so their rates have their own screen:

1. Open **WooCommerce > Banner extras pricing**.
2. Change one or more of:
   - **Pole pocket: rate per linear ft** (charged per foot of banner width, per pocket side: **Top only** and **Bottom only** are one side, **Top & Bottom** is two sides),
   - **Pole pocket: setup fee** (added whenever a pole pocket is chosen),
   - **Rope reinforcement: rate per linear ft** (charged per foot of the banner perimeter).
3. Press **Save changes**. The message **Banner extras pricing saved.** appears.
4. Check a banner in the shop: for example 24 x 16 in with **Top & Bottom** = 2 sides x 2 ft x rate + setup fee.

![78 WooCommerce > Banner extras pricing](images/78-admin-banner-extras.png)

> The wording in the label of the option (for example "Pole Pocket ($1.00 per linear ft + $10 setup)") is plain text for the customer. If you change a rate, change that label too in **PC Designer > Printing Options** (the field name).

**Other add-ons (Grommets, Welding, Wind Slits, Rush, Print-Sides)**

1. Open the option set, click the field (for example **Wind Slits**).
2. Each choice has its own price line; change the amount or the label and press **Update**.
3. **Rush Production** doubles the price of the item including its options.

![76 An add-on field in the option set](images/76-admin-addon-field.png)

**Quantity breaks**: the top of **Printing options** has **Quantity** with a **Quantity breaks** table (minimum quantity and discount per item). Leave it unless you want volume prices on a product.

### 3.5 Tier discounts

**B2B & B2C > Pricing Rules**, section **Tier Discount Ladder**:

| Tier | Discount | Meaning |
|---|---|---|
| **B2C - Retail price** | 0% | Everybody else |
| **Returning customer (5%)** | 5% | A customer moves here automatically after the second order |
| **B2B (10%)** | 10% | Approved business clients |
| **Reseller (20%)** | 20% | Resellers |

To change a discount press **Edit** on the tier row. The dialog **Edit pricing rule** opens: change **Discount** (a percentage, shown as "% off") and, if needed, **Rule name** or **Status**, then press **Save rule**. Leave **Tier key** as it is. The **Payment terms** field is a note for you; it does not change how checkout works. Older tiers that are not used are marked **Inactive**. Discounts apply to future carts and orders only; past orders do not change.

![80 Tier discount ladder](images/80-admin-tier-ladder.png)

![81 Edit a tier](images/81-admin-tier-edit.png)

Below the ladder you find **Quantity-Based Discounts** (extra discount for larger quantities; empty now) and **Special & Promo Rules**.

### 3.6 B2B settings and approving a B2B request

**B2B & B2C > Settings**, tab **B2B Settings**:

- **Allow self-registration** (shows **Apply for B2B** on the account page),
- **Auto-approve applications** (leave off to approve each request yourself),
- **Default tier on approval** (the tier a newly approved company gets).

![82 B2B settings](images/82-admin-b2b-settings.png)

**Approve a request**

1. When a customer applies, the **B2B & B2C** menu shows a red number. Open **B2B & B2C > B2B Stores**, tab **Pending applications**.
2. Click the company to see its details: company name, contact, tax id, address.
3. Click **Approve B2B Application**, add an optional note and confirm. The company gets the tier, the applicant gets an e-mail and sees the company portal at the next login.
4. To decline click **Reject Application**.

![83 B2B Stores](images/83-admin-b2b-stores.png)

### 3.7 Company management, private products and templates

1. **B2B & B2C > B2B Stores**, click a company. The detail page shows company information, the team (seats used), recent orders, **B2B settings** (tier, payment terms note, team seats) and the account owner.
2. **Edit store** changes the company name, address or tier. A company can be suspended: its members then lose the company prices.
3. **Invite store** (button at the top of the list) invites a company by e-mail; invitations are bound to that e-mail address and appear in **B2B & B2C > Invitations**.
4. **Private products**: in the company detail page the box **Private products** lists all products with a search field. Tick the products that only this company may see and buy and press **Save private products**. A product assigned to one or more companies is hidden from everyone else (shop, search, direct address, sitemap). **Currently assigned (n)** shows what is assigned.
5. **Hide public templates for this company** (same box): removes the public template gallery from this company's designer, so the company only sees its own templates.
6. **Customers** lists every customer with tier, orders and spend; **Customers > a name** lets you change a customer's tier by hand.
7. **Deleting a company** also deletes its Image Zone files, its templates and its private product assignments. Orders and the customers' accounts stay.

![84b Company detail: information, team, orders, settings](images/84b-admin-store-top.png)

![84 Company detail: Private products and Hide public templates](images/84-admin-store-detail.png)

![85 Invitations](images/85-admin-invitations.png)

![87 Customers](images/87-admin-customers.png)

### 3.8 Orders and the print-ready files

1. **WooCommerce > Orders** lists all orders. Click an order number to open it: customer, billing and shipping address, the items with all chosen options, the shipping method (for example **Pickup - Fort Pierce, FL**), the payment method (**Pay by invoice**), the status, notes, and on the right the box **Customer Design**. For an order that contains art, that box lists the design files of each line and opens them (see step 3).
2. Set the status as the order moves on (**Processing**, **Completed**, ...). The customer receives the matching e-mail. **Pay by invoice** orders start as **On hold**: send the PayPal invoice, then set **Processing** when you begin production.
3. In the box **Customer Design** open the design of the order line. The page **Detail product design** has the tabs **Resource**, **Create PDF**, **Download design** and **PDF Layout**. On **Download design** you find **SVG**, **PNG (High quality images)**, **JPG (CMYK with ICC profile)** and **PDF**, each with a **Download** button (the PDF has no watermark). **Resource** gives the customer's original uploaded files.
4. Download the files, print, then complete the order.

![90 WooCommerce > Orders](images/90-admin-orders.png)

![91b An order in the admin](images/91b-admin-order-edit.png)

![91 Detail product design: Download design](images/91-admin-order-design-files.png)

**Check:** the downloaded PDF has the size of the order (for example 24 x 16 in) and contains the art as uploaded. File types offered for download can be limited in **PC Designer > Settings** (**Design file type download**).

### 3.9 Quotes

1. **B2B & B2C > Custom Quotes** lists the requests from customers; the menu shows a red number for new ones.
2. Open a request, review products and notes, edit the prices, **Save** and **Send to Customer**.
3. The customer receives an e-mail and can **Accept** or **Decline** while signed in. Accepting converts the quote into an order.

![86 Custom Quotes](images/86-admin-quotes.png)

### 3.10 Menus and categories

- **Category bar** (Banner ... Misc): **Products > Categories**. The bar shows the top-level categories in the order set there (drag to reorder), with the category image as the icon. An empty category shows **Coming soon** until it gets a product.
- **Top menu** (Home, Shop, Blog, Gallery, About us, Contact us): **Appearance > Menus**.

![92 Products > Categories](images/92-admin-categories.png)

![93 Appearance > Menus](images/93-admin-menus.png)

### 3.11 Pages, blog and gallery

- **Pages > All Pages**: About us, Contact, Gallery and the legal pages (**Terms of Service**, **Privacy Policy**, **Refund Policy**, **Accessibility**) and **Track order**. Click a page to edit it; **Update** publishes. The legal pages start with a draft banner: delete the banner when you have reviewed and approved the text.
- **Posts > All Posts**: blog articles. **Add Post** writes a new one; set **Categories** and a featured image.
- **Gallery Items**: items of the Gallery page.
- Photos: **Media > Library**.

![94 Pages](images/94-admin-pages.png)

![95 Posts](images/95-admin-posts.png)

### 3.12 Users

**Users > All Users** lists all accounts. **Add User** creates an account; pick the role (**Administrator** for full access, **Shop manager** for orders and products only, **Customer** for shoppers). Each administrator should have a personal account and a strong password.

![96 Users](images/96-admin-users.png)

### 3.13 E-mail notifications

- **Order e-mails** (new order, processing, completed, ...): **WooCommerce > Settings > Emails**. Click **Manage** next to an e-mail to change its subject, heading and recipients. The e-mails that go to the shop (**New order**, **Cancelled order**, **Failed order**) are addressed to the shop address: check that it is yours.
- **Sender address and mail service**: **WP Mail SMTP**. The from address should be an address on your own domain.
- **Design notifications** (a customer saved a design, designs approved): **PC Designer > Settings > General**, section **Notifications**; **Recipients** lists who receives them.
- **B2B e-mails** (application received, approved, rejected, invitation, quote) are sent automatically by the store.

![97 WooCommerce > Settings > Emails](images/97-admin-emails.png)

![99 WP Mail SMTP](images/99-admin-smtp.png)

![A4 Design notification settings](images/A4-admin-designer-notifications.png)

### 3.14 Payment settings and connecting PayPal

**WooCommerce > Settings > Payments** lists the payment providers. Today:

- **Pay by invoice** is switched on: the customer places the order and you send a PayPal invoice before production. Click **Manage** to open its settings page (headed **Direct bank transfer**): the checkbox **Enable direct bank transfers**, and the fields **Title** ("Pay by invoice"), **Description** (shown at checkout) and **Instructions** (shown on the confirmation page and in the e-mail). **Account details** stays empty because no bank account is shown.
- **PayPal** (WooCommerce PayPal Payments) is installed but **not connected**. You connect your own PayPal business account at go-live (steps below).
- **Take offline payments** and **More payment options** list further methods; other providers (for example Paymentech) are separate projects.

![98 WooCommerce > Settings > Payments](images/98-admin-payments.png)

![A8 Pay by invoice settings](images/A8-admin-pay-by-invoice.png)

**Connect your PayPal account**

1. **WooCommerce > Settings > Payments**, click **Complete setup** next to **PayPal** (or open the PayPal settings directly).
2. On the page **Welcome to PayPal Payments** press **Activate PayPal Payments**, then follow the connection steps: sign in to your **PayPal business account** in the PayPal window and accept the permissions PayPal asks for. You enter your credentials only on PayPal's own pages, never in the store.
3. Back in the store, switch **PayPal** on, and decide whether **Pay by invoice** stays available next to it.
4. Open the cart and checkout once and check that the PayPal option appears; place a small test order and refund it.

![A9 PayPal settings: connect your own account](images/A9-admin-paypal.png)

### 3.15 Shipping

**WooCommerce > Settings > Shipping** holds the shipping zone **United States** with two methods: **Pickup - Fort Pierce, FL** (free) and **Ground shipping - quoted** (charges nothing at checkout; you quote the cost to the customer). To change a rate or add a method open the zone and its method, edit the cost, and save. Add further zones for other countries if you ship there.

![A7 WooCommerce > Settings > Shipping](images/A7-admin-shipping.png)

### 3.16 Settings you may need

- **PC Designer > Settings > Upload design**: allowed file types and limits. Leave them as delivered unless Printcart advises a change.
- **Settings > General**: site title, administration e-mail.
- **Settings > Reading**: **Search engine visibility** (see Part 4).
- **Users > Profile**: your own password.
- **UpdraftPlus**: backups.

![A3 PC Designer upload settings](images/A3-admin-designer-upload-settings.png)

![A2 Settings > General](images/A2-admin-general.png)

![A5 Users > Profile: new password](images/A5-admin-profile-password.png)

![A6 Backups](images/A6-admin-backups.png)

---

## 4. Go-live checklist

Printcart does the technical steps marked **(Printcart)**; you do the steps marked **(you)**.

| # | Step | Who | Done |
|---|---|---|---|
| 1 | **Domain:** Printcart sends you the DNS records for giganticprints.com; you (or your DNS provider) change them. Keep your Google mail (MX) records exactly as they are. The store switches to the new address and the secure padlock (HTTPS) is checked. | you + Printcart | [ ] |
| 2 | **Search engines:** in **Settings > Reading** untick **Discourage search engines from indexing this site** and press **Save Changes**. Until then the store is hidden from Google on purpose. | Printcart / you | [ ] |
| 3 | **Passwords:** the administrator password that was sent to you by e-mail during the project must be changed. **Users > Profile > Set New Password**. Create a personal administrator account for each person and remove accounts you do not recognise. | you | [ ] |
| 4 | **Payment:** connect your own PayPal business account (section 3.14: **WooCommerce > Settings > Payments > PayPal**), decide whether **Pay by invoice** stays, and check that checkout offers the payment method. | you | [ ] |
| 5 | **Shipping:** check the zone **United States** (**Pickup - Fort Pierce, FL**, **Ground shipping - quoted**), set the rates and zones you want (3.15), and check **Tax**. | you + Printcart | [ ] |
| 6 | **Legal pages:** read the drafts of **Terms of Service**, **Privacy Policy**, **Refund Policy** and **Accessibility**, correct them (or send your own text) and remove the draft banner. | you | [ ] |
| 7 | **Sender e-mail:** set the from address in **WP Mail SMTP** and **WooCommerce > Settings > Emails**, and the **Recipients** for design notifications in **PC Designer > Settings**, to addresses on your domain. Send a test e-mail. | you + Printcart | [ ] |
| 8 | **Test order:** place one real order with a small print file; check the order e-mails, the download links in the order, the PayPal invoice you send, and the confirmation page. Then cancel or refund it. | you + Printcart | [ ] |
| 9 | **Remove the demo data:** the sample companies (**Demo Signs Co**, **Demo Reseller Co**) with their Image Zone image, template and the product assigned to Demo Signs Co; the demo customer accounts and their sample orders (#2046 to #2049); sample quotes and invitations. | Printcart | [ ] |
| 10 | **Public pages:** unpublish pages that are not part of your store (sample and placeholder pages). | Printcart | [ ] |
| 11 | **Designer licence:** already active for giganticprints.com and the staging address (Enterprise, no expiry): all designer features and unlimited print-file downloads. Nothing to do. | Printcart | [x] |
| 12 | **Updates and backup:** all software updates installed, a fresh backup taken and a restore test noted. | Printcart | [ ] |
| 13 | **Sign-off:** the person who approves the go-live confirms in writing (Part 5, question 6). | you | [ ] |
| 14 | **After go-live:** check the first real orders the same day and tell us about anything unexpected. | you | [ ] |

Where to untick the search engine box (step 2):

![A1 Settings > Reading: Search engine visibility](images/A1-admin-reading.png)

---

## 5. Questions for you

Please answer these so we can finish the details.

1. **Wind slits:** is the price **$0.50 per square foot** of the banner, or a **flat** amount per banner? Today the option charges a flat $0.50 and its label says so.
2. **Pole pocket setup fee:** is the **$10 setup** charged **per item**, or **once per order line** (one size on the order, whatever the quantity)? Today it is charged per item.
3. **Payment:** is PayPal the payment method you want at go-live, and who connects the PayPal business account? Other providers (for example Paymentech) and accounting export (for example QuickBooks) are separate projects; tell us if you want a proposal.
4. **Shipping:** which shipping methods and rates do you want (pickup, ground shipping with a fixed or table rate, other countries)? Today there are **Pickup - Fort Pierce, FL** (free) and **Ground shipping - quoted** (no charge at checkout).
5. **Legal pages:** please review the drafts of **Terms of Service**, **Privacy Policy**, **Refund Policy** and **Accessibility** (footer links) and send corrections or your own text.
6. **Sign-off:** who approves the go-live on your side, and in which form (an e-mail with "Approved to go live" is enough)?
7. **Grommets:** which positions do you want (all sides, top, bottom, left, right, custom) and should **Yes** be the default? Today the choices are **No grommets**, **4 corners only** and **Every 2 ft**, default **No grommets**.
8. **Webbing mesh:** should it be an option? Please send the specification and the price.
9. **Returning customer 5%:** today a customer moves to this tier after the second order. Is that the rule you want, or another one (number of orders, spend, time)?
10. **Resellers (20%):** how do you recognise a reseller: do you approve them yourself in **B2B Stores**?
11. **Handheld and Misc:** which products belong in these two categories? Until they have products they show **Coming soon**.
12. **Mail:** which address should orders, quotes and design notifications go to, and which address should customers see as the sender?
13. **Item list:** how important is the item list (several items with different art in one order)? It is the next big feature on your list.
14. **Administrators:** who needs an administrator login and who only needs to process orders (Shop manager)?

---

## 6. Support and care

After go-live the store is hosted and looked after by **Printcart**.

**What care covers**

- Hosting of the store and its secure connection (HTTPS), managed by Printcart.
- Regular backups and the ability to restore them.
- Software updates and security fixes for the store, the designer and its extensions, tested before they go live.
- Monitoring that the store is reachable and that ordering works.
- Help with questions about using this guide, the admin screens and the designer.
- Small corrections to what was delivered (for example wording, a price table row, a menu item) when they are reported together.

**What is quoted separately**

- New features and larger changes (for example the item list, new product families, integrations such as accounting export or additional payment methods).
- Content work (writing pages and articles, photography).

**How to ask for help**

- Write to **ceo@printcart.com** or message **WhatsApp +84 937 869 689**, with a short description, the page address, and a screenshot if possible. For an order, add the order number.
- Reports about orders that cannot be placed are looked at first.
- For any change to prices or products you can make yourself, use Part 3. If you are unsure, ask Printcart before you change anything on the live store.

**David Nguyen Quang Huy**  
Chief Executive Officer, Printcart · Netbase JSC  
ceo@printcart.com · WhatsApp +84 937 869 689

---

## Appendix A. Acceptance checklist

Tick each line when you have tried it on the staging store (https://giganticprints.printcart.com). The paths are added to that address. Please send us the list with your notes.

| Done | What you asked for (your words) | Where to try it |
|---|---|---|
| [ ] | "One site" for visitors, B2C and B2B | `/` |
| [ ] | Menu on top in Signs365 order: Banner, Rigid, Adhesive, Handheld, Magnet, Apparel, Misc | `/` (category bar) |
| [ ] | "Upload on the configuration screen", the designer only optional | `/create-your-own/?product_id=1168` |
| [ ] | "A PREVIEW of the art on the product with real measurements" | upload a file in the designer (above) |
| [ ] | "The size of the item to be printed is based on the uploaded item" | same page, after the upload |
| [ ] | "Change the size on the same screen", "center art or stretch to fit" | same page: **Stretch to fit**, **Fit (keep ratio)**, **Center**, W and H boxes |
| [ ] | Big files (20 MB up to 200 MB) in PDF, AI, PNG, JPG; warning for low resolution | same page: **Photos** tab |
| [ ] | No preview without art: "Upload Artwork or Create New" | same page, press **Add to Cart** with an empty print area |
| [ ] | The price changes while I choose size and options | `/product/banner-13oz-single-sided/` and the designer **Printing** tab |
| [ ] | Banner: 13oz, 15oz, 18oz (double sided only 18oz), welding yes, rope no, pole pocket, wind slits, rush 100% | designer **Printing** tab |
| [ ] | Rigid: Coroplast, PVC, Acrylic, Aluminum, J-Bond, Foamcore | `/product-category/rigid/` |
| [ ] | Adhesive / vinyl types and laminate | `/product-category/adhesive/` |
| [ ] | Magnets with preset sizes, one product | `/product-category/magnet/` |
| [ ] | Apparel / DTF by linear inch | `/product-category/apparel/` |
| [ ] | One "Magnets" product with sizes as options, banner weights as options | the two products above |
| [ ] | Design without login; login or create an account for the cart and checkout | `/create-your-own/?product_id=1168`, then `/checkout/` |
| [ ] | Register like a B2C customer | `/my-account/` |
| [ ] | Ask to be upgraded to B2B | `/b2b-portal/?section=apply` (logged in) |
| [ ] | Super Admin approves the request and the company gets its discount (10% B2B, 20% reseller, 5% returning) | admin: `/wp-admin/admin.php?page=printcart-stores-b2b` |
| [ ] | B2B sees the discounted price once, in designer, product page and cart | any product, logged in as a company member |
| [ ] | "Image Zone" for the company | `/b2b-portal/?section=images` |
| [ ] | Private template library: save, edit, clone, use for an order | `/b2b-portal/?section=templates` |
| [ ] | Reorder in one click with the old art | `/b2b-portal/?section=orders` (**Reorder**) |
| [ ] | Products only for one client | the product assigned to Demo Signs Co: `/product/foamcore-single-sided/` (member vs visitor) |
| [ ] | Checkout completes; shipping and payment choice | `/checkout/` (**Pickup**, **Pay by invoice**) |
| [ ] | Footer with contact, Terms and Conditions | `/terms/` and the footer |
| [ ] | Print-ready files for every order | admin: **WooCommerce > Orders** > order > **Customer Design** |
| [ ] | Rates of pole pocket and rope can be changed | admin: `/wp-admin/admin.php?page=gp-linear-rates` |

Not part of this release (see 1.3): item list, chat and FAQ, Super Admin Custom Order Form, accounting export, additional payment methods.

**David Nguyen Quang Huy**  
Chief Executive Officer, Printcart · Netbase JSC  
ceo@printcart.com · WhatsApp +84 937 869 689
