import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Product, ProductsPage } from '../models/product.model';

@Injectable({ providedIn: 'root' })
export class ProductReaderService {
  private readonly apiUrl = '/api/products';

  constructor(private http: HttpClient) {}

  getAll(params?: { name?: string; page?: number; per_page?: number }): Observable<ProductsPage> {
    let httpParams = new HttpParams();
    if (params?.name) httpParams = httpParams.set('name', params.name);
    if (params?.page) httpParams = httpParams.set('page', params.page);
    if (params?.per_page) httpParams = httpParams.set('per_page', params.per_page);

    return this.http.get<ProductsPage>(this.apiUrl, { params: httpParams });
  }

  getById(id: string): Observable<Product> {
    return this.http.get<Product>(`${this.apiUrl}/${id}`);
  }
}
