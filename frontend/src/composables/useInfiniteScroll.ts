import { ref, onMounted, onUnmounted } from 'vue'

export function useInfiniteScroll(
  containerRef: { value: HTMLElement | null },
  callback: () => void,
  options?: { threshold?: number },
) {
  const isLoading = ref(false)
  const threshold = options?.threshold ?? 200
  let observer: IntersectionObserver | null = null
  const sentinel = ref<HTMLElement | null>(null)

  function onIntersect(entries: IntersectionObserverEntry[]) {
    if (entries[0]?.isIntersecting && !isLoading.value) {
      callback()
    }
  }

  function observe(el: HTMLElement) {
    sentinel.value = el
    observer = new IntersectionObserver(onIntersect, {
      root: containerRef.value,
      rootMargin: `${threshold}px`,
    })
    observer.observe(el)
  }

  function disconnect() {
    observer?.disconnect()
    observer = null
  }

  onUnmounted(disconnect)

  return {
    isLoading,
    sentinel,
    observe,
    disconnect,
  }
}
