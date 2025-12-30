FROM python:3.11-slim

# Set environment variables
ENV PYTHONUNBUFFERED=1 \
    DEBIAN_FRONTEND=noninteractive \
    PYTHONDONTWRITEBYTECODE=1

# Install system dependencies (only ffmpeg needed for conversion)
RUN apt-get update -qq && \
    apt-get install -y --no-install-recommends \
    ffmpeg \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /scripts

# Copy requirements file
COPY python/requirements.txt /scripts/requirements.txt

# Upgrade pip and install dependencies
RUN pip install --no-cache-dir --upgrade pip setuptools wheel && \
    pip install --no-cache-dir -r requirements.txt

# Expose port
EXPOSE 353
