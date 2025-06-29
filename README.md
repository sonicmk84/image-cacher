# Image Cacher – Pornstar Feed Caching App

This project is a technical test for a PHP developer position. It fetches a JSON feed of pornstar data, stores it in a local SQLite database, downloads and caches associated images, and exposes a RESTful API to interact with the data.

## 🔧 Features

- Laravel 12-based PHP application
- Dockerized for easy deployment
- Pulls and processes large JSON feed of pornstar data
- Caches all unique image URLs locally
- Efficient insertion with upserts and batch processing
- RESTful API to interact with `pornstars` and their thumbnails
- Local image serving through Laravel's `storage` symlink

## 🚀 Getting Started

### 1. Clone the repository

```bash
git clone https://github.com/sonicmk84/image-cacher.git
cd image-cacher
```

### 2. Build and start the Docker container

```bash
docker-compose up --build
```
### 3. Install dependencies inside the container

```bash
docker-compose exec app composer install
```

### 4. Run migrations

```bash
touch database/database.sqlite
docker-compose exec app php artisan migrate
```

### 5. Create storage link (for local thumbnails to be served)

```bash
docker-compose exec app php artisan storage:link
```

### 6. Run unit tests

```bash
docker-compose exec app php artisan test
```

### 7. Run the feed sync command
```bash
docker-compose run --rm artisan sync:pornstar-feed
```
⚠️ This operation takes time with the full dataset due to image downloading.
- Running the feed sync a second time will not re-download images if they have already been cached, but will download any new/missed/failed images.
- In case something goes wrong with the feed sync, and you want to stop it and try from scratch: Run:
```bash
docker ps --filter "name=artisan" --format "{{.ID}}" | xargs -r docker kill
```
to kill the process. Then delete content of `\storage\app\public\thumbnails` and delete `database\database.sqlite`. Finally, run the migrations step again.

## 🔗 API Endpoints

Base URL: http://localhost:8080/api

| Method | Endpoint          | Description                  |
| ------ | ----------------- | ---------------------------- |
| GET    | `/pornstars`      | Paginated list of pornstars  |
| GET    | `/pornstars/{id}` | Get pornstar with thumbnails |
| POST   | `/pornstars`      | Create new pornstar          |
| PUT    | `/pornstars/{id}` | Update a pornstar            |
| DELETE | `/pornstars/{id}` | Delete a pornstar            |


## 📂 Project Structure

- app/Console/Commands/SyncPornstarFeed.php – core logic for data fetch & caching
- app/Models/Pornstar.php and Thumbnail.php – Eloquent models
- routes/api.php – API route definitions
- storage/app/public/thumbnails/ – cached images
- database/database.sqlite – SQLite database

## ✅ Notes

- Built and tested on Windows 10 with Docker Desktop
- PHP version: 8.4.8 (inside container)
- Laravel version: 12.19.3
- Queue system and Redis were explored but not used in the final version for simplicity

## 🙋 Contact
- Maintained by sonicmk84
- Full Name: Michael Kallika
- Email: michael.kallika@gmail.com
