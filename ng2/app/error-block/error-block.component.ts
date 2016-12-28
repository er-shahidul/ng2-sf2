import {Component, OnInit, Input} from '@angular/core';
import {FormControl} from "@angular/forms";

@Component({
  selector: 'app-error-block',
  template: `
      <ul *ngIf="el.touched && el.invalid">
      <li *ngFor="let error of errors">{{ parseError(error) }}</li>
</ul>
  `,
  styles: []
})
export class ErrorBlockComponent implements OnInit {

  @Input() el: FormControl;

  constructor() { }

  ngOnInit() {
  }

  get errors(){

    if (!this.el.errors) return [];
    let obj = this.el.errors;

    return Object.keys(obj).map(function (key) {
      return {key: key, value: obj[key]}
    });
  }

  parseError(error) {
    if (typeof error.value == 'string') {
      return error.value;
    } else if (error.key == 'required') {
      return 'This field is required';
    }

    return 'Invalid';
  }

}
