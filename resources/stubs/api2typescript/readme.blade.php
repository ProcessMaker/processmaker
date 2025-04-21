# ProcessMaker SDK

This SDK provides typed access to the ProcessMaker API.

## Features

- Fully typed API interfaces
- Composable hooks for easy integration
@foreach ($tags as $tag)
- Complete implementation of the ProcessMaker {{ ucfirst(strtolower($tag)) }} API
@endforeach

## Usage

@if ($firstTag)
### {{ ucfirst(strtolower($firstTag)) }} API

```typescript
import { useProcessMakerApi } from '~/composables/useProcessMakerApi';
import { useProcessMaker{{ ucfirst(strtolower($firstTag)) }} } from 'shared';

// In your component or service
const api = useProcessMakerApi();
const {{ strtolower($firstTag) }}Api = useProcessMaker{{ ucfirst(strtolower($firstTag)) }}(api);

// Example: Get all {{ strtolower($firstTag) }}
const get{{ ucfirst(strtolower($firstTag)) }} = async () => {
  try {
    const response = await {{ strtolower($firstTag) }}Api.get{{ ucfirst(strtolower($firstTag)) }}();
    return response.data;
  } catch (error) {
    console.error('Error fetching {{ strtolower($firstTag) }}:', error);
    throw error;
  }
};
```
@endif

## Available APIs

Currently, the SDK includes:

@foreach ($tags as $tag)
- **{{ ucfirst(strtolower($tag)) }} API**: Complete implementation of the ProcessMaker {{ ucfirst(strtolower($tag)) }} API
@endforeach 