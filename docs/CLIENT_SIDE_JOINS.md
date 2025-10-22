# Client-Side Joins Guide

This guide shows you how to work with related data using the PHP-CRUD-API-Generator. Instead of complex server-side joins, you fetch the data you need and combine it on the client side - giving you complete control and flexibility.

## 📋 Table of Contents

- [Why Client-Side?](#why-client-side)
- [Basic Examples](#basic-examples)
- [Advanced Patterns](#advanced-patterns)
- [Language-Specific Examples](#language-specific-examples)
- [Performance Tips](#performance-tips)
- [Best Practices](#best-practices)

---

## Why Client-Side?

### ✅ Advantages

1. **Flexibility** - Client decides what to fetch and when
2. **Control** - Different clients have different needs (mobile vs web)
3. **Caching** - Easier to cache individual resources
4. **Performance** - Parallel requests can be faster than complex joins
5. **Simplicity** - API stays simple and maintainable
6. **Standard Practice** - How most REST APIs work (GitHub, Stripe, etc.)

### 🎯 When It Works Best

- Different views need different data structures
- Mobile apps need minimal data
- You want to cache resources independently
- You need fine-grained control over loading

### 🤔 When You Might Want Auto-Joins

- Many nested relationships (3+ levels)
- High latency network (every request is expensive)
- GraphQL-like requirements
- **→ But implement this only when users actually need it!**

---

## Basic Examples

### Example 1: Users and Posts

**Database Structure:**
```sql
users (id, name, email)
posts (id, user_id, title, content)
```

**Fetch Related Data:**

```javascript
// 1. Get user
const user = await fetch('/api.php?action=read&table=users&id=123')
  .then(r => r.json());

console.log(user);
// { id: 123, name: "John Doe", email: "john@example.com" }

// 2. Get user's posts
const posts = await fetch('/api.php?action=list&table=posts&filter=user_id:123')
  .then(r => r.json());

console.log(posts.data);
// [
//   { id: 1, user_id: 123, title: "My First Post", ... },
//   { id: 2, user_id: 123, title: "Second Post", ... }
// ]

// 3. Combine on client
const userWithPosts = {
  ...user,
  posts: posts.data
};

console.log(userWithPosts);
// {
//   id: 123,
//   name: "John Doe",
//   email: "john@example.com",
//   posts: [
//     { id: 1, title: "My First Post", ... },
//     { id: 2, title: "Second Post", ... }
//   ]
// }
```

---

### Example 2: Orders with Items and Products

**Database Structure:**
```sql
orders (id, customer_id, total, created_at)
order_items (id, order_id, product_id, quantity, price)
products (id, name, sku, description)
```

**Fetch Complete Order:**

```javascript
async function getOrderWithDetails(orderId) {
  // 1. Get order
  const order = await fetch(`/api.php?action=read&table=orders&id=${orderId}`)
    .then(r => r.json());

  // 2. Get order items
  const items = await fetch(`/api.php?action=list&table=order_items&filter=order_id:${orderId}`)
    .then(r => r.json());

  // 3. Get all products in one request (using IN operator)
  const productIds = items.data.map(item => item.product_id).join('|');
  const products = await fetch(`/api.php?action=list&table=products&filter=id:in:${productIds}`)
    .then(r => r.json());

  // 4. Create product lookup
  const productMap = {};
  products.data.forEach(product => {
    productMap[product.id] = product;
  });

  // 5. Combine data
  return {
    order: order,
    items: items.data.map(item => ({
      ...item,
      product: productMap[item.product_id]
    }))
  };
}

// Usage
const orderDetails = await getOrderWithDetails(456);
console.log(orderDetails);
// {
//   order: { id: 456, customer_id: 789, total: 99.99, ... },
//   items: [
//     {
//       id: 1, quantity: 2, price: 29.99,
//       product: { id: 101, name: "Widget", sku: "WDG-001", ... }
//     },
//     {
//       id: 2, quantity: 1, price: 39.99,
//       product: { id: 102, name: "Gadget", sku: "GDG-002", ... }
//     }
//   ]
// }
```

---

### Example 3: Blog with Comments and Authors

**Database Structure:**
```sql
posts (id, user_id, title, content, created_at)
comments (id, post_id, user_id, text, created_at)
users (id, name, avatar)
```

**Fetch Post with Comments and Authors:**

```javascript
async function getPostWithComments(postId) {
  // Parallel fetching for speed!
  const [post, comments] = await Promise.all([
    fetch(`/api.php?action=read&table=posts&id=${postId}`).then(r => r.json()),
    fetch(`/api.php?action=list&table=comments&filter=post_id:${postId}`).then(r => r.json())
  ]);

  // Get all unique user IDs
  const userIds = new Set([
    post.user_id,
    ...comments.data.map(c => c.user_id)
  ]);

  // Fetch all users in one request
  const users = await fetch(
    `/api.php?action=list&table=users&filter=id:in:${[...userIds].join('|')}&fields=id,name,avatar`
  ).then(r => r.json());

  // Create user lookup
  const userMap = {};
  users.data.forEach(user => {
    userMap[user.id] = user;
  });

  // Assemble complete data
  return {
    ...post,
    author: userMap[post.user_id],
    comments: comments.data.map(comment => ({
      ...comment,
      author: userMap[comment.user_id]
    }))
  };
}

// Usage
const blogPost = await getPostWithComments(789);
console.log(blogPost);
// {
//   id: 789,
//   title: "My Blog Post",
//   content: "...",
//   author: { id: 123, name: "John Doe", avatar: "..." },
//   comments: [
//     {
//       id: 1, text: "Great post!",
//       author: { id: 456, name: "Jane Smith", avatar: "..." }
//     }
//   ]
// }
```

---

## Advanced Patterns

### Pattern 1: Batch Fetching with IN Operator

Instead of N queries, use one query with the IN operator:

```javascript
// ❌ BAD: N queries (slow)
for (const postId of postIds) {
  const comments = await fetch(`/api.php?action=list&table=comments&filter=post_id:${postId}`);
  // Process comments...
}

// ✅ GOOD: 1 query (fast)
const postIdsString = postIds.join('|');  // "1|2|3|4|5"
const allComments = await fetch(
  `/api.php?action=list&table=comments&filter=post_id:in:${postIdsString}`
).then(r => r.json());

// Group by post_id on client
const commentsByPost = {};
allComments.data.forEach(comment => {
  if (!commentsByPost[comment.post_id]) {
    commentsByPost[comment.post_id] = [];
  }
  commentsByPost[comment.post_id].push(comment);
});
```

---

### Pattern 2: Parallel Requests

Fetch multiple independent resources simultaneously:

```javascript
// ✅ GOOD: All requests happen at once
const [user, posts, followers, likes] = await Promise.all([
  fetch('/api.php?action=read&table=users&id=123').then(r => r.json()),
  fetch('/api.php?action=list&table=posts&filter=user_id:123').then(r => r.json()),
  fetch('/api.php?action=list&table=followers&filter=following_id:123').then(r => r.json()),
  fetch('/api.php?action=list&table=likes&filter=user_id:123').then(r => r.json())
]);

const profile = {
  user,
  posts: posts.data,
  followerCount: followers.meta.total,
  likeCount: likes.meta.total
};
```

---

### Pattern 3: Repository Layer (Best Practice)

Create a data access layer that encapsulates the join logic:

```javascript
// api/repositories/UserRepository.js
class UserRepository {
  constructor(apiBase = '/api.php') {
    this.apiBase = apiBase;
  }

  async get(userId) {
    const response = await fetch(
      `${this.apiBase}?action=read&table=users&id=${userId}`
    );
    return response.json();
  }

  async getPosts(userId, page = 1) {
    const response = await fetch(
      `${this.apiBase}?action=list&table=posts&filter=user_id:${userId}&page=${page}`
    );
    return response.json();
  }

  async getWithPosts(userId) {
    const [user, posts] = await Promise.all([
      this.get(userId),
      this.getPosts(userId)
    ]);

    return {
      ...user,
      posts: posts.data,
      postCount: posts.meta.total
    };
  }

  async getProfileData(userId) {
    const [user, posts, followers] = await Promise.all([
      this.get(userId),
      this.getPosts(userId, 1),
      this.getFollowers(userId)
    ]);

    return {
      user,
      recentPosts: posts.data.slice(0, 5),
      followerCount: followers.meta.total
    };
  }

  async getFollowers(userId) {
    const response = await fetch(
      `${this.apiBase}?action=list&table=followers&filter=following_id:${userId}`
    );
    return response.json();
  }
}

// Usage in your app
const userRepo = new UserRepository();
const profile = await userRepo.getProfileData(123);
```

---

## Language-Specific Examples

### PHP Client

```php
<?php
class ApiClient {
    private $baseUrl = 'http://localhost/api.php';

    public function getUserWithPosts($userId) {
        // Fetch user
        $user = json_decode(
            file_get_contents("{$this->baseUrl}?action=read&table=users&id={$userId}"),
            true
        );

        // Fetch posts
        $posts = json_decode(
            file_get_contents("{$this->baseUrl}?action=list&table=posts&filter=user_id:{$userId}"),
            true
        );

        // Combine
        return [
            'user' => $user,
            'posts' => $posts['data'] ?? []
        ];
    }
}

$client = new ApiClient();
$data = $client->getUserWithPosts(123);
print_r($data);
```

---

### Python Client

```python
import requests
from typing import Dict, List

class ApiClient:
    def __init__(self, base_url: str = 'http://localhost/api.php'):
        self.base_url = base_url

    def get_user_with_posts(self, user_id: int) -> Dict:
        # Fetch user
        user = requests.get(
            self.base_url,
            params={'action': 'read', 'table': 'users', 'id': user_id}
        ).json()

        # Fetch posts
        posts = requests.get(
            self.base_url,
            params={'action': 'list', 'table': 'posts', 'filter': f'user_id:{user_id}'}
        ).json()

        # Combine
        return {
            'user': user,
            'posts': posts.get('data', [])
        }

# Usage
client = ApiClient()
data = client.get_user_with_posts(123)
print(data)
```

---

### React Component

```javascript
import { useState, useEffect } from 'react';

function UserProfile({ userId }) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function loadData() {
      try {
        // Parallel fetch
        const [user, posts, followers] = await Promise.all([
          fetch(`/api.php?action=read&table=users&id=${userId}`).then(r => r.json()),
          fetch(`/api.php?action=list&table=posts&filter=user_id:${userId}&sort=-created_at&page_size=5`).then(r => r.json()),
          fetch(`/api.php?action=count&table=followers&filter=following_id:${userId}`).then(r => r.json())
        ]);

        setData({
          user,
          recentPosts: posts.data,
          followerCount: followers.count
        });
      } catch (error) {
        console.error('Failed to load profile:', error);
      } finally {
        setLoading(false);
      }
    }

    loadData();
  }, [userId]);

  if (loading) return <div>Loading...</div>;
  if (!data) return <div>Error loading profile</div>;

  return (
    <div>
      <h1>{data.user.name}</h1>
      <p>{data.followerCount} followers</p>
      <h2>Recent Posts</h2>
      {data.recentPosts.map(post => (
        <article key={post.id}>
          <h3>{post.title}</h3>
          <p>{post.content}</p>
        </article>
      ))}
    </div>
  );
}
```

---

## Performance Tips

### 1. Use Field Selection

Only fetch the fields you need:

```javascript
// ❌ Fetch everything (wasteful)
const users = await fetch('/api.php?action=list&table=users');

// ✅ Only fetch needed fields (efficient)
const users = await fetch('/api.php?action=list&table=users&fields=id,name,avatar');
```

---

### 2. Implement Client-Side Caching

```javascript
class CachedApiClient {
  constructor() {
    this.cache = new Map();
    this.cacheDuration = 5 * 60 * 1000; // 5 minutes
  }

  async getUser(userId) {
    const cacheKey = `user_${userId}`;
    const cached = this.cache.get(cacheKey);

    if (cached && Date.now() - cached.timestamp < this.cacheDuration) {
      console.log('Cache hit:', cacheKey);
      return cached.data;
    }

    console.log('Cache miss:', cacheKey);
    const data = await fetch(`/api.php?action=read&table=users&id=${userId}`)
      .then(r => r.json());

    this.cache.set(cacheKey, {
      data,
      timestamp: Date.now()
    });

    return data;
  }

  invalidate(pattern) {
    for (const key of this.cache.keys()) {
      if (key.includes(pattern)) {
        this.cache.delete(key);
      }
    }
  }
}

const api = new CachedApiClient();

// First call - fetches from API
const user1 = await api.getUser(123);

// Second call - returns from cache
const user2 = await api.getUser(123);

// Invalidate after update
await updateUser(123, { name: 'New Name' });
api.invalidate('user_123');
```

---

### 3. Pagination for Large Datasets

```javascript
async function getAllUserPosts(userId) {
  const posts = [];
  let page = 1;
  let hasMore = true;

  while (hasMore) {
    const response = await fetch(
      `/api.php?action=list&table=posts&filter=user_id:${userId}&page=${page}&page_size=100`
    ).then(r => r.json());

    posts.push(...response.data);

    hasMore = page < response.meta.pages;
    page++;
  }

  return posts;
}
```

---

### 4. Use COUNT for Statistics

```javascript
// ❌ Fetch all data just to count (wasteful)
const posts = await fetch('/api.php?action=list&table=posts&filter=user_id:123');
const postCount = posts.data.length;

// ✅ Use count endpoint (efficient)
const count = await fetch('/api.php?action=count&table=posts&filter=user_id:123')
  .then(r => r.json());
const postCount = count.count;
```

---

## Best Practices

### ✅ DO

1. **Use parallel requests** when fetching independent resources
2. **Use IN operator** to batch fetch related records
3. **Implement caching** at the client level
4. **Use field selection** to reduce payload size
5. **Create repository classes** to encapsulate join logic
6. **Handle errors gracefully** - one failed request shouldn't break everything

### ❌ DON'T

1. **Don't make sequential requests** when you can parallelize
2. **Don't fetch full records** when you only need a few fields
3. **Don't ignore caching** - it dramatically improves performance
4. **Don't fetch data you don't need** "just in case"
5. **Don't repeat join logic** - abstract it into reusable functions

---

## Complete Real-World Example

Here's a complete example of a blog system with users, posts, and comments:

```javascript
// BlogAPI.js - Complete data access layer
class BlogAPI {
  constructor(baseUrl = '/api.php') {
    this.baseUrl = baseUrl;
  }

  // Base fetch method
  async fetch(action, params = {}) {
    const query = new URLSearchParams({ action, ...params });
    const response = await fetch(`${this.baseUrl}?${query}`);
    if (!response.ok) throw new Error(`API error: ${response.status}`);
    return response.json();
  }

  // Users
  async getUser(id) {
    return this.fetch('read', { table: 'users', id });
  }

  async getUsers(ids) {
    return this.fetch('list', {
      table: 'users',
      filter: `id:in:${ids.join('|')}`,
      fields: 'id,name,avatar'
    });
  }

  // Posts
  async getPost(id) {
    return this.fetch('read', { table: 'posts', id });
  }

  async getUserPosts(userId, page = 1) {
    return this.fetch('list', {
      table: 'posts',
      filter: `user_id:${userId}`,
      sort: '-created_at',
      page,
      page_size: 10
    });
  }

  // Comments
  async getPostComments(postId) {
    return this.fetch('list', {
      table: 'comments',
      filter: `post_id:${postId}`,
      sort: 'created_at'
    });
  }

  // High-level: Post with everything
  async getPostWithDetails(postId) {
    // Parallel fetch post and comments
    const [post, comments] = await Promise.all([
      this.getPost(postId),
      this.getPostComments(postId)
    ]);

    // Get unique user IDs
    const userIds = new Set([
      post.user_id,
      ...comments.data.map(c => c.user_id)
    ]);

    // Fetch all users
    const users = await this.getUsers([...userIds]);
    const userMap = {};
    users.data.forEach(u => userMap[u.id] = u);

    // Assemble
    return {
      ...post,
      author: userMap[post.user_id],
      comments: comments.data.map(c => ({
        ...c,
        author: userMap[c.user_id]
      }))
    };
  }

  // High-level: User profile
  async getUserProfile(userId) {
    const [user, posts, postCount] = await Promise.all([
      this.getUser(userId),
      this.getUserPosts(userId, 1),
      this.fetch('count', { table: 'posts', filter: `user_id:${userId}` })
    ]);

    return {
      user,
      recentPosts: posts.data.slice(0, 5),
      totalPosts: postCount.count
    };
  }
}

// Usage examples
const api = new BlogAPI();

// Get single post with author and comments
const post = await api.getPostWithDetails(123);
console.log(post.title);
console.log(post.author.name);
console.log(post.comments.length + ' comments');

// Get user profile
const profile = await api.getUserProfile(456);
console.log(profile.user.name);
console.log(profile.totalPosts + ' total posts');
console.log('Recent:', profile.recentPosts);
```

---

## Summary

**Client-side joins give you:**
- ✅ Complete control over data fetching
- ✅ Flexibility for different use cases
- ✅ Better caching opportunities
- ✅ Simpler API implementation
- ✅ Standard REST practices

**Remember:**
1. Use the **IN operator** to batch fetch related records
2. Use **Promise.all()** for parallel requests
3. Implement a **repository layer** to abstract join logic
4. Use **field selection** to minimize payload
5. Implement **caching** for frequently accessed data

This approach works great for most applications. Only implement auto-joins when users specifically request it and you have clear performance data showing it's needed!

---

**Questions or need help?** Open an issue on GitHub!
