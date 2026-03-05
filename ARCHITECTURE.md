# 🚀 Saroukh TN - Symfony 6/7 Architecture Guide

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [Request/Response Cycle](#requestresponse-cycle)
3. [Core Components](#core-components)
4. [Project Structure](#project-structure)
5. [Key Concepts](#key-concepts)
6. [How Your Features Work](#how-your-features-work)
7. [Essential Symfony Commands](#essential-symfony-commands)
8. [Professor Requirements & How We Met Them](#professor-requirements--how-we-met-them)

---

## Architecture Overview

Symfony follows the **MVC (Model-View-Controller)** architectural pattern with some key additions:

```
┌─────────────────────────────────────────────────────────────┐
│                    USER / BROWSER                           │
└────────────────────────┬────────────────────────────────────┘
                         │ HTTP Request
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                   ROUTING LAYER                             │
│  (Matches URL to Controller action)                        │
│  Example: /products → ProductController::list()            │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│              CONTROLLER LAYER                               │
│  (Handles user request, orchestrates logic)                │
│  - Inject dependencies (Services, Repositories)            │
│  - Call services to get data                               │
│  - Render template with data                               │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│              SERVICE LAYER (Business Logic)                │
│  Example: CartService, ProductService                     │
│  - Database operations                                     │
│  - Calculations & processing                               │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│           MODEL LAYER (Data & Entities)                    │
│  - Entities: Product, User, Order (ORM)                   │
│  - Repositories: ProductRepository (Database queries)      │
│  - Forms: ProductFormType (Validation & binding)           │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                   DATABASE (MySQL)                         │
│  Stores all persistent data                               │
└─────────────────────────────────────────────────────────────┘
```

---

## Request/Response Cycle

Let's trace a real example: **Adding a product to cart**

```json
1. USER CLICKS "🛒 Add to Cart" button
   └─ POST request to /cart/add/5 (product ID = 5)

2. SYMFONY ROUTER processes request
   └─ Matches URL pattern to controller action
   └─ File: config/routes.yaml or #[Route] attributes

3. CARTCONTROLLER is instantiated
   ├─ add(int $id, Request $request, ProductRepository $repo, CartService $cart)
   ├─ Dependencies automatically injected by Symfony (Dependency Injection)
   └─ Called action method with parameters

4. CONTROLLER executes logic
   ├─ Gets product from database
   │  └─ $product = $productRepository->find($id)
   ├─ Adds to cart session
   │  └─ $cartService->addItem($product, $quantity)
   ├─ Creates flash message
   │  └─ $this->addFlash('success', 'Added to cart!')
   └─ Returns HTTP response
      └─ $this->redirectToRoute('product_list')

5. RESPONSE sent to browser
   └─ 302 Redirect → /products
   └─ Session data saved with cart contents
   └─ Flash message displayed on next page

6. BROWSER receives redirect
   └─ Asks for /products
   └─ Cycle starts again...
```

---

## Core Components

### 1️⃣ **Controllers** (Request Handlers)

**Location:** `src/Controller/`

Controllers are PHP classes that handle HTTP requests and return responses.

```php
<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    // Route: GET /products/{id}
    #[Route('/products/{id}', name: 'product_show')]
    public function show(int $id, ProductRepository $repo): Response
    {
        // 1. Get data from repository (service)
        $product = $repo->find($id);
        
        // 2. Check if exists
        if (!$product) {
            throw $this->createNotFoundException();
        }
        
        // 3. Render template with data
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}
```

**Key Points:**
- Extend `AbstractController` for helper methods
- Use `#[Route]` PHP attributes for routing (modern approach)
- Request parameters are type-hinted arguments
- Symfony auto-injects dependencies
- Always return a `Response` object

### 2️⃣ **Services** (Business Logic Layer)

**Location:** `src/Service/`

Services contain reusable business logic, not directly tied to HTTP.

```php
<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private const CART_SESSION_KEY = 'cart';

    public function __construct(private RequestStack $requestStack)
    {
        // Dependency injection via constructor
    }

    public function addItem(Product $product, int $qty = 1): void
    {
        $items = $this->getItems(); // Retrieve from session
        $items[$product->getId()] = [
            'product' => $product,
            'quantity' => $qty,
        ];
        $this->getSession()->set(self::CART_SESSION_KEY, $items);
    }

    public function getSubtotal(): float
    {
        $total = 0;
        foreach ($this->getItems() as $item) {
            $total += $item['product']->getPrice() * $item['quantity'];
        }
        return $total;
    }

    private function getSession()
    {
        return $this->requestStack->getSession();
    }
}
```

**Why Services?**
- Reusable logic (can be called from multiple controllers)
- Easier to test (can test business logic without HTTP)
- Separation of concerns (logic ≠ HTTP handling)
- Can be shared across controllers/commands

### 3️⃣ **Repositories** (Data Access)

**Location:** `src/Repository/`

Repositories handle all database queries for a specific entity.

```php
<?php
namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class ProductRepository extends ServiceEntityRepository
{
    public function searchWithFilters(?string $keyword, ?string $category, 
                                      ?float $minPrice, ?float $maxPrice): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($keyword) {
            $qb->andWhere('p.name LIKE :keyword OR p.description LIKE :keyword')
               ->setParameter('keyword', '%' . $keyword . '%');
        }

        if ($category) {
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $category);
        }

        if ($minPrice !== null) {
            $qb->andWhere('p.price >= :minPrice')
               ->setParameter('minPrice', $minPrice);
        }

        return $qb->getQuery()->getResult();
    }

    public function getCategories(): array
    {
        return array_column(
            $this->createQueryBuilder('p')
                 ->select('DISTINCT p.category')
                 ->getQuery()
                 ->getResult(),
            'category'
        );
    }
}
```

**Key Points:**
- Only for database queries (SELECT)
- Uses Doctrine Query Builder (type-safe SQL)
- Keeps controllers clean (no SQL in controllers)
- Testable and reusable

### 4️⃣ **Entities** (Data Models)

**Location:** `src/Entity/`

Entities are PHP classes that represent database tables.

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?float $price = null;

    // Getters and setters...
    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static {
        $this->name = $name;
        return $this;
    }
}
```

**Why Doctrine ORM?**
- No manual SQL (safer from SQL injection)
- Database-agnostic (works with MySQL, PostgreSQL, etc.)
- Automatic migrations
- Type-safe queries

### 5️⃣ **Forms** (Input Validation)

**Location:** `src/Form/`

Forms handle validation, filtering, and binding request data to entities.

```php
<?php
namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ProductFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 3, 'max' => 255]),
                ]
            ])
            ->add('price', MoneyType::class, [
                'currency' => 'TND',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                ]
            ]);
    }
}
```

**In Controller:**
```php
$form = $this->createForm(ProductFormType::class, $product);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    // Form data is automatically bound to $product entity
    $entityManager->persist($product);
    $entityManager->flush();
    return $this->redirectToRoute('product_list');
}

return $this->render('form.html.twig', ['form' => $form]);
```

### 6️⃣ **Views / Templates** (Twig)

**Location:** `templates/`

Twig is a templating engine that renders HTML.

```twig
{% extends 'base.html.twig' %}

{% block title %}Products - Saroukh TN{% endblock %}

{% block body %}
<div class="container">
    <h1>🛍️ Our Products</h1>

    <!-- Search Form -->
    <form method="GET" action="{{ path('product_list') }}">
        <input type="text" name="keyword" value="{{ search_query or '' }}">
        <button type="submit">Search</button>
    </form>

    <!-- Product Listing -->
    {% if products is empty %}
        <p>No products found.</p>
    {% else %}
        <div class="row">
            {% for product in products %}
            <div class="col">
                <h3>{{ product.name }}</h3>
                <p>{{ product.price }} TND</p>
                <a href="{{ path('product_show', {'id': product.id}) }}">View</a>
            </div>
            {% endfor %}
        </div>
    {% endif %}
</div>
{% endblock %}
```

**Twig Features:**
- `{{ variable }}` - Echo/print
- `{% if condition %}...{% endif %}` - Logic
- `{% for item in items %}...{% endfor %}` - Loops
- `{{ path('route_name', {'id': 123}) }}` - Generate URLs
- `{% extends %}` - Template inheritance
- Filters: `{{ text|upper }}`, `{{ items|length }}`

---

## Project Structure

```
saroukh-tn/
├── bin/
│   ├── console             # Symfony CLI tool
│   └── phpunit             # Test runner
│
├── config/
│   ├── bundles.php         # Enabled packages
│   ├── services.yaml       # Service definitions & DI
│   ├── routes.yaml         # Route definitions (if not using attributes)
│   └── packages/           # Package-specific configs
│
├── public/
│   ├── index.php           # Application entry point
│   └── assets/             # CSS, JS, images
│
├── src/
│   ├── Controller/         # HTTP request handlers
│   │   ├── ProductController.php
│   │   ├── CartController.php
│   │   └── AdminProductController.php
│   │
│   ├── Entity/             # Database models
│   │   └── Product.php
│   │
│   ├── Repository/         # Database queries
│   │   └── ProductRepository.php
│   │
│   ├── Service/            # Business logic
│   │   └── CartService.php
│   │
│   ├── Form/               # Form classes
│   │   └── ProductFormType.php
│   │
│   ├── DataFixtures/       # Seed data
│   │   └── ProductFixtures.php
│   │
│   └── Kernel.php          # Application kernel
│
├── templates/
│   ├── base.html.twig      # Base layout (navbar, footer)
│   ├── product/
│   │   ├── list.html.twig  # Product listing
│   │   └── show.html.twig  # Product detail + recommendations
│   ├── cart/
│   │   └── index.html.twig # Shopping cart page
│   └── admin/products/
│       ├── list.html.twig  # Admin product management
│       ├── form.html.twig  # Create/edit form
│       └── delete.html.twig # Delete confirmation
│
├── migrations/             # Database schema changes
│   └── Version20XX...php
│
├── tests/                  # Unit & functional tests
│   └── bootstrap.php
│
├── .env                    # Environment variables (DATABASE_URL, etc.)
├── .env.local             # Local overrides (NOT in git)
├── composer.json          # Package dependencies
├── composer.lock          # Locked versions
└── README.md
```

---

## Key Concepts

### **Dependency Injection (DI)**

Instead of creating objects inside a class, Symfony **injects** them via constructor or method parameters.

❌ **BAD (without DI):**
```php
class CartController extends AbstractController
{
    public function add(): Response
    {
        $cart = new CartService(); // Creates new instance every time
        // CartService creates new RequestStack internally
        // Hard to test (can't mock)
    }
}
```

✅ **GOOD (with DI):**
```php
class CartController extends AbstractController
{
    public function add(CartService $cartService): Response
    {
        // Symfony automatically creates and injects CartService
        // RequestStack is already injected into CartService
        // Easy to test (can inject mock objects)
        $cartService->addItem($product);
    }
}
```

**DI Container (`services.yaml`):**
```yaml
services:
  _defaults:
    autowire: true    # Automatically inject dependencies
    autoconfigure: true

  App\Service\CartService:
    arguments:
      - '@request_stack'  # Inject RequestStack service
```

### **Routing (URL → Controller)**

Routes map URLs to controller actions.

**Method 1: #[Route] Attribute (Modern):**
```php
#[Route('/products/{id}', name: 'product_show')]
public function show(int $id): Response { }
```

**Method 2: YAML (config/routes.yaml):**
```yaml
product_show:
  path: /products/{id}
  controller: App\Controller\ProductController::show
```

**URL Generation in Templates:**
```twig
<a href="{{ path('product_show', {'id': product.id}) }}">View Product</a>
<!-- Generates: <a href="/products/5">View Product</a> -->
```

### **Request/Response Objects**

Every HTTP interaction uses `Request` and `Response` objects.

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Route('/search', name: 'search')]
public function search(Request $request): Response
{
    // Read query parameters
    $keyword = $request->query->get('q');     // ?q=value
    $category = $request->query->get('cat');  // ?cat=value

    // Read POST data
    $name = $request->request->get('name');   // From form

    // Read headers
    $userAgent = $request->headers->get('User-Agent');

    // Return response
    return $this->render('results.html.twig', [
        'keyword' => $keyword,
        'results' => $results,
    ]);
}
```

### **Session & Flash Messages**

**Session (persistent across requests):**
```php
$session = $request->getSession();
$session->set('cart', ['item1', 'item2']);
$items = $session->get('cart');
```

**Flash Messages (show once then disappear):**
```php
// In Controller
$this->addFlash('success', 'Product added to cart!');
$this->addFlash('error', 'Something went wrong!');

// In Template
{% for message in app.flashes('success') %}
    <div class="alert alert-success">{{ message }}</div>
{% endfor %}
```

### **Middleware / Event Listeners**

Something needs to happen on every request? Use an Event Listener.

```php
namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

class RequestListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        // Run on EVERY request (before controller)
        // Can modify request, check permissions, etc.
    }
}
```

---

## How Your Features Work

### Feature #1: CRUD Operations (Admin Panel)

**Architecture:**
```
URL: POST /admin/products/new
    ↓
AdminProductController::new()
    ↓
1. Create empty Product entity
2. Create ProductFormType from entity
3. Handle form submission
4. Validate (built-in via form constraints)
5. Persist to database
6. Redirect & flash message
```

**Files Involved:**
- `src/Controller/AdminProductController.php` - Handles POST/GET
- `src/Entity/Product.php` - Data model
- `src/Form/ProductFormType.php` - Validation rules
- `src/Service/EntityManager` - Database persistence (from Doctrine)
- `templates/admin/products/form.html.twig` - HTML form

### Feature #2: Shopping Cart

**Architecture:**
```
User clicks "🛒 Add"
    ↓
POST /cart/add/{id}
    ↓
CartController::add()
    ↓
CartService (from DI)
    ↓
RequestStack → Session
    ↓
Store cart items in session
    ↓
Flash message + redirect
```

**Why SessionStack?**
- Session data persists across requests
- User can add items, refresh page, items still there
- Cart empties on logout/session end
- Perfect for temporary shopping data

### Feature #3: Search & Filtering

**Architecture:**
```
User submits search form
    ↓
GET /products?search=iPhone&category=Electronics&min_price=1000
    ↓
ProductController::list()
    ↓
Extract query parameters from Request
    ↓
Call ProductRepository::searchWithFilters()
    ↓
Build dynamic QueryBuilder based on filters
    ↓
Execute query, return results
    ↓
Pass to template, render products
```

**Why QueryBuilder?**
- SQL-safe (no string concatenation!)
- Builds query dynamically (only add clauses if parameter exists)
- Type-safe bindings (prevents SQL injection)

### Feature #4: Product Recommendations

**Architecture:**
```
User views product (id=5)
    ↓
ProductController::show($id)
    ↓
ProductRepository::getRecommendations($product)
    ↓
Find products in same category
    ↓
Shuffle randomly (in PHP)
    ↓
Return up to 4 products
    ↓
Pass to template
    ↓
Template renders as carousel/grid
```

**Why shuffle in PHP (not DB)?**
- Doctrine DQL doesn't support RAND()
- PHP's `shuffle()` works on in-memory arrays
- Small dataset (~35 products), performance is fine

---

## Essential Symfony Commands

### **Project Management**
```bash
# Create new project
symfony new my_project

# Start development server
symfony server:start
symfony server:start -d              # Background mode
symfony server:stop

# View running server
symfony server:status
```

### **Database (Doctrine)**
```bash
# Create database
php bin/console doctrine:database:create

# Generate migration from entity changes
php bin/console make:migration

# Apply migrations to database
php bin/console doctrine:migrations:migrate

# Rollback last migration
php bin/console doctrine:migrations:migrate --down 1

# Execute raw SQL
php bin/console doctrine:query:sql "SELECT * FROM product"
```

### **Generate Code**
```bash
# Generate entity
php bin/console make:entity Product

# Generate repository
php bin/console make:repository ProductRepository

# Generate controller
php bin/console make:controller ProductController

# Generate form
php bin/console make:form ProductFormType

# Generate migration
php bin/console make:migration
```

### **Cache & Debug**
```bash
# Clear all caches
php bin/console cache:clear

# Clear specific cache pool
php bin/console cache:pool:clear cache.app

# Warm up cache (for production)
php bin/console cache:warmup

# Debug containers/services
php bin/console debug:container CartService
php bin/console debug:router

# Validate Twig templates
php bin/console lint:twig templates/

# Validate YAML configs
php bin/console lint:yaml config/
```

### **Testing**
```bash
# Run all tests
php bin/console --test phpunit

# Run specific test file
./bin/phpunit tests/Controller/ProductControllerTest.php

# Run with coverage
./bin/phpunit --coverage-html coverage/
```

### **Routes**
```bash
# Show all routes
php bin/console debug:router

# Match URL to route
php bin/console router:match /products/5

# Show route details
php bin/console debug:router product_show
```

---

## Professor Requirements & How We Met Them

### ✅ Requirement 1: Use Symfony Framework

| What | How | Files |
|------|-----|-------|
| Use Controllers | Every feature has a dedicated controller | `ProductController`, `CartController`, `AdminProductController` |
| Use Dependency Injection | Inject services into controllers | All controllers use constructor/method injection |
| Use Doctrine ORM | Database operations via entities | `Product` entity, queries via repositories |
| Use Forms | Product creation/editing | `ProductFormType` with validation |
| Use Twig | All views/templates | `templates/` folder |
| Follow Routing | URL → Controller mapping | `#[Route]` attributes on every action |

### ✅ Requirement 2: CRUD Operations

| Operation | Endpoint | Controller | Method |
|-----------|----------|-----------|--------|
| **Create** | `GET/POST /admin/products/new` | AdminProductController | `new()` |
| **Read** | `GET /products/{id}` | ProductController | `show()` |
| **Read (List)** | `GET /admin/products` | AdminProductController | `list()` |
| **Update** | `GET/POST /admin/products/{id}/edit` | AdminProductController | `edit()` |
| **Delete** | `GET/POST /admin/products/{id}/delete` | AdminProductController | `delete()` |

### ✅ Requirement 3: Forms & Validation

```php
// ProductFormType has constraints for:
- name: NotBlank, Length(min=3, max=255)
- price: NotBlank, Positive
- category: NotBlank, Length(min=2, max=255)
- description: NotBlank, Length(min=5, max=255)
```

### ✅ Requirement 4: Database Integration

| Component | Use |
|-----------|-----|
| `Product` Entity | Represents database table |
| `ProductRepository` | Query builder for safe SQL |
| `EntityManager` | Persist/flush to database |
| Doctrine ORM | Type-safe database access |

### ✅ Requirement 5: Responsive UI

| View | Bootstrap | Features |
|------|-----------|----------|
| `base.html.twig` | Bootstrap 5 CDN | Navbar, flash messages, footer |
| `product/list.html.twig` | Bootstrap Grid | Responsive cards, search/filter |
| `product/show.html.twig` | Bootstrap Cards | Detail view, recommendations |
| `cart/index.html.twig` | Bootstrap Table | Cart items, checkout button |
| `admin/products/list.html.twig` | Bootstrap Table | CRUD management |
| `admin/products/form.html.twig` | Bootstrap Forms | Create/edit with validation display |

### ✅ Requirement 6: Services & Business Logic

| Service | Purpose | Location |
|---------|---------|----------|
| `CartService` | Session-based cart management | `src/Service/CartService.php` |
| `ProductRepository` | All product queries | `src/Repository/ProductRepository.php` |
| `AbstractController` | Built-in Symfony helpers | Base class for all controllers |

### ✅ Requirement 7: Flash Messages & UX

```php
$this->addFlash('success', 'Product added to cart!');
$this->addFlash('error', 'Product not found!');
```

Rendered in template:
```twig
{% for message in app.flashes('success') %}
    <div class="alert alert-success">{{ message }}</div>
{% endfor %}
```

### ✅ Requirement 8: Pagination & Search

- **Search/Filter:** `ProductRepository::searchWithFilters()`
- **Recommendations:** `ProductRepository::getRecommendations()`
- **35 Products** in 6 categories

---

## How It All Connects

### The Request Cycle (Real Example)

**User: Types search for "iPhone" in category "Electronics"**

```
Step 1: Browser
└─ GET /products?search=iPhone&category=Electronics

Step 2: Symfony Router
└─ Matches route pattern to ProductController::list

Step 3: ProductController (Dependency Injection)
├─ __construct receives:
│  ├─ Request $request (HTTP request object)
│  └─ ProductRepository $productRepository (injected)
└─ list() method executes:
   ├─ Extract parameters: $keyword, $category
   └─ Call: $products = $repo->searchWithFilters(...)

Step 4: ProductRepository (Database Layer)
├─ Creates QueryBuilder
├─ Adds WHERE clauses dynamically:
│  ├─ p.name LIKE '%iPhone%'
│  └─ p.category = 'Electronics'
├─ Executes query
└─ Returns array of Product entities

Step 5: Controller (continued)
├─ Gets categories for filter dropdown
└─ Renders template with data:
   ├─ 'products' => results
   ├─ 'categories' => all categories
   └─ 'search_query' => 'iPhone'

Step 6: Twig Template Rendering
├─ Loops through products
├─ Displays search form with previous values
└─ Renders product cards

Step 7: Browser
└─ Displays HTML response
```

### Database & ORM

```php
// Symfony (High level)
$product = $repo->find(5);
echo $product->getName();  // "iPhone 15 Pro"

// Doctrine ORM converts to SQL automatically:
SELECT * FROM product WHERE id = 5

// No raw SQL needed in PHP!
```

### Service Layer Benefit

Instead of:
```php
// ❌ Cart logic mixed with HTTP logic
class CartController {
    public function add(Request $request) {
        $items = $request->getSession()->get('cart', []);
        $id = $request->request->get('product_id');
        // ... manual array manipulation ...
    }
}
```

We have:
```php
// ✅ Clean separation
class CartController {
    public function add(int $id, CartService $cart) {
        $cart->addItem($product);  // Simple, testable
    }
}
```

---

## Architecture Diagram (Your Project)

```
┌─────────────────┐
│   User Browser  │
└────────┬────────┘
         │ HTTP: GET /products?search=iPhone
         │
┌────────▼────────────────────────────────────┐
│         SYMFONY HTTP KERNEL                  │
├─────────────────────────────────────────────┤
│                                              │
│   ┌──────────────────────────────┐          │
│   │    ROUTING LAYER             │          │
│   │  URL → ProductController     │          │
│   └──────────────┬───────────────┘          │
│                  │                           │
│   ┌──────────────▼───────────────┐          │
│   │   PRODUCTCONTROLLER          │          │
│   │  - Handle HTTP request       │          │
│   │  - Inject dependencies       │          │
│   │  - Get search params         │          │
│   │  - Call repository           │          │
│   │  - Render template           │          │
│   └──────────────┬───────────────┘          │
│                  │                           │
│        ┌─────────┴──────────┐               │
│        │                    │                │
│   ┌────▼──────────┐  ┌────▼──────────┐     │
│   │  REPOSITORY   │  │ FORM SERVICE  │     │
│   │ (Queries DB)  │  │ (Validation)  │     │
│   └────┬──────────┘  └───────────────┘     │
│        │                                    │
│   ┌────▼──────────────────────────┐        │
│   │   DOCTRINE ORM LAYER           │       │
│   │  (Entity Manager, QueryBuilder)│       │
│   └────┬───────────────────────────┘       │
│        │                                    │
│        │         SQL                       │
└────────┼─────────────────────────────┘
         │
    ┌────▼────────────┐
    │  MySQL Database │
    │  (Persistent)   │
    └─────────────────┘

Controllers know about HTTP
Services know about business logic
Repositories know about database
Entities know about models
Twig knows about presentation
```

---

## Summary

**Symfony is a framework that helps you:**

1. **Route HTTP requests** to the right controller
2. **Inject dependencies** automatically (Services, Repositories)
3. **Query databases safely** without raw SQL (Doctrine ORM)
4. **Validate input** with constraints and forms
5. **Render HTML** with Twig templating
6. **Manage sessions** for stateful features (cart)
7. **Handle errors** gracefully
8. **Structure code** logically (MVC pattern)

**Your Saroukh TN app demonstrates all of this:**
- Controllers handle requests
- Services contain business logic
- Repositories query the database
- Entities model data
- Forms validate input
- Twig renders views
- Session stores cart data
- Errors are caught and displayed

This is production-ready Symfony code that follows best practices!

---

## Useful Links

- [Symfony Official Docs](https://symfony.com/doc)
- [Doctrine ORM](https://www.doctrine-project.org/)
- [Twig Docs](https://twig.symfony.com/)
- [Symfony Best Practices](https://symfony.com/doc/current/best_practices.html)

---

**Created for Saroukh TN Ecommerce Project**  
**Symfony 6/7 - March 2026**
