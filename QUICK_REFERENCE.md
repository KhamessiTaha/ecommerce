# Symfony Quick Reference Guide - Saroukh TN

## 🔥 Most Common Commands

```bash
# Start dev server
symfony server:start

# Create/migrate database
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# Generate components
php bin/console make:controller CartController
php bin/console make:entity Product
php bin/console make:form ProductFormType

# Debug help
php bin/console debug:router              # All routes
php bin/console debug:container CartService
php bin/console router:match /products/5  # Which route matches?

# Clear cache (if something seems broken)
php bin/console cache:clear
```

---

## 📁 File Locations

| What | Location | Example |
|------|----------|---------|
| URL handlers | `src/Controller/` | `ProductController.php` |
| Database models | `src/Entity/` | `Product.php` |
| Database queries | `src/Repository/` | `ProductRepository.php` |
| Business logic | `src/Service/` | `CartService.php` |
| Forms | `src/Form/` | `ProductFormType.php` |
| HTML templates | `templates/` | `product/list.html.twig` |
| Routes | Controller `#[Route]` attributes | On method in controller |
| Database migrations | `migrations/` | Auto-generated |
| Config | `config/` | `services.yaml`, `routes.yaml` |

---

## 🎯 Creating a New Feature

### Step 1: Create Entity (Model)
```bash
php bin/console make:entity Product
# Answer prompts:
# - name: string(255)
# - price: float
# - category: string(255)
# - description: text
```

### Step 2: Generate Migration
```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Step 3: Generate Repository
```bash
php bin/console make:repository ProductRepository
```

### Step 4: Create Controller
```bash
php bin/console make:controller ProductController
```

### Step 5: Add Routes & Logic
```php
#[Route('/products/{id}', name: 'product_show')]
public function show(int $id, ProductRepository $repo): Response
{
    $product = $repo->find($id);
    return $this->render('product/show.html.twig', [
        'product' => $product,
    ]);
}
```

### Step 6: Create Template
```bash
# Create templates/product/show.html.twig
```

---

## 💡 Common Patterns

### Get Data from Database
```php
// Find by ID
$product = $repository->find(1);

// Find first that matches
$product = $repository->findOneBy(['name' => 'iPhone']);

// Find multiple
$products = $repository->findBy(['category' => 'Electronics']);

// Custom query
$products = $repository->searchByKeyword('iPhone');
```

### Handle HTTP Form (POST)
```php
public function create(Request $request, EntityManagerInterface $em): Response
{
    $product = new Product();
    $form = $this->createForm(ProductFormType::class, $product);
    $form->handleRequest($request);
    
    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($product);
        $em->flush();
        
        $this->addFlash('success', 'Created!');
        return $this->redirectToRoute('product_list');
    }
    
    return $this->render('product/form.html.twig', [
        'form' => $form,
    ]);
}
```

### Use a Service
```php
// In Controller
public function addToCart(int $id, CartService $cart): Response
{
    // CartService is auto-injected
    $cart->addItem($product, $quantity);
    return $this->redirectToRoute('cart_view');
}

// In Service
public function addItem(Product $product, int $qty): void
{
    $items = $this->getItems();
    $items[$product->getId()] = ['product' => $product, 'qty' => $qty];
    $this->session->set('cart', $items);
}
```

### Query with Conditions
```php
// In Repository
public function searchByName(string $keyword): array
{
    return $this->createQueryBuilder('p')
        ->where('p.name LIKE :keyword')
        ->setParameter('keyword', '%' . $keyword . '%')
        ->orderBy('p.name', 'ASC')
        ->getQuery()
        ->getResult();
}
```

### Render Template with Data
```php
return $this->render('product/list.html.twig', [
    'products' => $products,
    'total_count' => count($products),
    'search_query' => $keyword,
]);
```

### Flash Message (Show & disappear)
```php
// In Controller
$this->addFlash('success', 'Product saved!');
$this->addFlash('error', 'Something went wrong!');

// In Template
{% for message in app.flashes('success') %}
    <div class="alert alert-success">{{ message }}</div>
{% endfor %}
```

### Get Request Data
```php
// URL: /search?q=iPhone&category=Electronics
public function search(Request $request): Response
{
    $keyword = $request->query->get('q');       // ?q=...
    $category = $request->query->get('category');
    
    // POST data
    $name = $request->request->get('name');
    
    // Headers
    $userAgent = $request->headers->get('User-Agent');
}
```

---

## 🎨 Twig Template Cheat Sheet

### Echo Variables
```twig
{{ product.name }}
{{ product.price | number_format(2) }} TND
```

### Conditionals
```twig
{% if products is empty %}
    <p>No results</p>
{% elseif products|length == 1 %}
    <p>Found 1 product</p>
{% else %}
    <p>Found {{ products|length }} products</p>
{% endif %}
```

### Loops
```twig
{% for product in products %}
    <h3>{{ product.name }}</h3>
    <p>Price: {{ product.price }}</p>
{% else %}
    <p>No products</p>
{% endfor %}

{# With index #}
{% for product in products %}
    #{{ loop.index }}: {{ product.name }}
{% endfor %}
```

### Generate URLs
```twig
<a href="{{ path('product_show', {'id': product.id}) }}">View</a>
<!-- /products/5 -->

<form action="{{ path('cart_add', {'id': product.id}) }}" method="POST">
```

### Template Inheritance
```twig
{# base.html.twig #}
<html>
  <body>
    {% block content %}Default{% endblock %}
  </body>
</html>

{# product/list.html.twig #}
{% extends 'base.html.twig' %}

{% block content %}
    {{ parent() }}
    <h1>Products</h1>
{% endblock %}
```

### Filters
```twig
{{ text|upper }}              {# UPPERCASE #}
{{ text|lower }}              {# lowercase #}
{{ text|capitalize }}         {# Capitalize #}
{{ 123.456|number_format(2) }} {# 123.46 #}
{{ text|slice(0, 50) ~ '...' }} {# Truncate #}
{{ array|length }}            {# Count #}
{{ array|join(', ') }}        {# Implode #}
```

### Loops Info
```twig
{% for item in items %}
    {% if loop.first %}First item{% endif %}
    {% if loop.last %}Last item{% endif %}
    
    Item #{{ loop.index }}     {# 1-based #}
    Index: {{ loop.index0 }}   {# 0-based #}
{% endfor %}
```

### Includes (Reuse templates)
```twig
{% include 'product/_card.html.twig' with {'product': product} %}
```

---

## 🧪 Testing Quick Check

### Run All Tests
```bash
php bin/console --test phpunit

# Or directly
./bin/phpunit
```

### Run One Test File
```bash
./bin/phpunit tests/Controller/ProductControllerTest.php
```

### Run One Test Method
```bash
./bin/phpunit tests/Controller/ProductControllerTest.php::testShowProduct
```

---

## 🐛 Debugging Tips

### See what's happening
```bash
# Check all routes
php bin/console debug:router

# Match URL to controller
php bin/console router:match /products/5

# See service info
php bin/console debug:container CartService

# Check configuration
php bin/console config:dump-reference framework

# Lint templates
php bin/console lint:twig templates/
```

### Common Errors

| Error | Fix |
|-------|-----|
| "Service not found" | Add `#[Autowire]` or check `services.yaml` |
| "Table doesn't exist" | Run `php bin/console doctrine:migrations:migrate` |
| "Method not found" | Check entity getters: `public function getName()` |
| "Route not found" | Check `#[Route]` attribute on controller method |
| "Template not found" | Check path matches folder structure exactly |
| "QueryException: RAND()" | Use PHP `shuffle()` instead of DB RAND() |

---

## 📋 Your Project Structure at a Glance

```
Controls HTTP ↓
src/Controller/ProductController.php
src/Controller/CartController.php
src/Controller/AdminProductController.php
    ↓ uses
src/Service/CartService.php
src/Form/ProductFormType.php
src/Repository/ProductRepository.php
    ↓ queries
src/Entity/Product.php
    ↓ mapped to
Database (product table)
    ↓ rendered by
templates/product/*.html.twig
templates/admin/products/*.html.twig
templates/cart/*.html.twig
```

---

## 🚀 Deploy to Production Checklist

```bash
# 1. Update .env.local with production DB
# DATABASE_URL="mysql://user:pass@prodhost/dbname"

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Clear & warm cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# 4. Run migrations
php bin/console doctrine:migrations:migrate --env=prod

# 5. Set APP_ENV=prod in .env

# 6. Disable debug toolbar (set APP_DEBUG=0)

# 7. Check permissions on var/ folder
chmod -R 755 var/
```

---

## 📞 Help Commands

```bash
# List all available commands
php bin/console list

# Help for specific command
php bin/console help make:controller

# See Symfony version
php bin/console --version

# Check project status
symfony check:requirements
```

---

**Bookmark this file! You'll use it constantly when coding features.**

**Last Updated: March 2026**
