import { {{ $className }} } from './{{ $tagLower }}.api';

describe('{{ $className }}', () => {
  // Mock API client
  const mockApiClient = {
    head: vi.fn(),
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
    patch: vi.fn(),
  };

  // Create instance
  let api: {{ $className }};

  beforeEach(() => {
    api = new {{ $className }}(mockApiClient);
    vi.resetAllMocks();
  });

@foreach ($methods as $method)

  describe('{{ $method['methodName'] }}', () => {
    it('{{ $method['summary'] }}', async () => {
      const mockResponse = {!! $helper->json($helper->mockResponse($method), 6) !!};
      mockApiClient.{{ $method['httpMethod'] }}.mockResolvedValue(mockResponse);

      const result = await api.{{ $method['methodName'] }}({!! $helper->mockParams($method) !!});

      expect(mockApiClient.{{ $method['httpMethod'] }}).toHaveBeenCalledWith({!! $helper->mockUrl($method) !!});
      expect(result).toEqual(mockResponse);
    });
  });

@endforeach
});
