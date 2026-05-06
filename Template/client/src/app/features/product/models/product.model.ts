export interface Product {
  id: string;
  name: string;
  sku: string;
  price_in_cents: number;
  price_formatted: string;
  created_at: string;
}

export interface ProductsPage {
  data: Product[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
  };
}
