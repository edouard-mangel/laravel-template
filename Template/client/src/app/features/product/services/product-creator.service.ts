import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

interface CreateProductPayload {
  name: string;
  sku: string;
  price_in_cents: number;
}

interface CreateProductResponse {
  id: string;
}

@Injectable({ providedIn: 'root' })
export class ProductCreatorService {
  private readonly apiUrl = '/api/products';

  constructor(private http: HttpClient) {}

  create(payload: CreateProductPayload): Observable<CreateProductResponse> {
    return this.http.post<CreateProductResponse>(this.apiUrl, payload);
  }
}
