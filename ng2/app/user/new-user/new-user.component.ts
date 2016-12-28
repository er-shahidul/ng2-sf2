import { Component, OnInit } from '@angular/core';
import {FormGroup, FormControl, Validators} from "@angular/forms";
import {UserService} from "../user.service";
import {Router} from "@angular/router";

@Component({
  selector: 'app-new-user',
  templateUrl: './new-user.component.html',
  styleUrls: ['./new-user.component.scss']
})
export class NewUserComponent implements OnInit {

  userForm;
  constructor(protected userService:UserService, private router: Router) { }

  ngOnInit() {
    this.userForm = new FormGroup({
      'username': new FormControl('', [Validators.required]),
      'fullName': new FormControl('', [Validators.required]),
      'email': new FormControl('', [Validators.required, NewUserComponent.validateEmail]),
    });
  }

  onSubmit(){
    this.userService.addUser(this.userForm.value)
        .subscribe(users => {
            this.router.navigate(['/users/list']);
        });
  }

  static validateEmail(c:FormControl) {
    const re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    if (!re.test(c.value)) {
      return {email: 'Invalid Email'};
    }

    return null;
  }
}
