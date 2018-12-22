$(document).ready(function(){

    function InstallForm(){
        this.events();
    }

    InstallForm.prototype = Object.create(CheckError.prototype);

    InstallForm.prototype.events = function(){
        var self = this;
        $("#do_install").on("click", function(){
            self.sendForm();
        });
        $(".form-control").on("blur", function(){
            self.clearErrorField(this);
        });
    }

    InstallForm.prototype.sendForm = function(){
        try{
            this.clearErrorMessage();
            if(!this.emptyFields()){
                if(this.comparePasswords()){
                    this.clearErrorMessage();
                    this.reqSendForm();
                    return true;
                }
                throw new Error("password");
            }
            throw new Error("required");
        }catch(e){
            this.setErrorMessage(e.message);
        }
    }

    InstallForm.prototype.loaderShow = function(){
        $("#wrap_loader_block").show();
        $("#auth_wrap").hide();
    }

    InstallForm.prototype.successInstall = function(data){
        $("#wrap_loader_block").hide();
        $("#auth_wrap").show();
        if(data){
            var infoText = "<h3>";
            infoText += data.messageTitle;
            infoText += "</h3>";
            infoText += "<a href='/admin'>";
            infoText += data.message;
            infoText += "</a>";
            $("#install").html(infoText);
        }
        this.reloadPageTimeout(3000);
    }

    InstallForm.prototype.reloadPageTimeout = function(time){
        setTimeout(function(){
            document.location.href = "/admin";
        },time);
    }

    InstallForm.prototype.errorInstall = function(){
        $("#wrap_loader_block").hide();
        $("#auth_wrap").show();
    }

    InstallForm.prototype.reqSendForm = function(){
        var self = this;
        $.ajax({
            url: "install.php/?do_install=1",
            data: $("form").serialize(),
            beforeSend: function(){
                self.loaderShow();
            },
            success: function(data){
                data = JSON.parse(data);
                if(data){
                    if(data.status == "installed"){
                        self.successInstall(data);
                    }
                }
                self.errorInstall();
            },
            error: function(error){
                self.errorInstall();
                var responseJson = JSON.parse(error.response);
                if(responseJson){
                    if(responseJson.errorCode == 501){
                        $("[name=db_name]").addClass("has-error");
                    }else if(responseJson.errorCode == 503){
                        $("[name=db_host],[name=db_login],[name=db_password]").addClass("has-error");
                    }
                    alert(responseJson.description);
                }
            }
        })
    }



    function CheckError(){

    }

    CheckError.prototype.clearErrorFields = function(){
        $(".form-control").removeClass("has-error");
    }

    CheckError.prototype.clearErrorField = function(element){
        $(element).removeClass("has-error");
    }

    CheckError.prototype.setErrorMessage = function(type){
        switch (type){
            case "password":
                $(".passwordError").show();
                break;
            case "required":
                $(".emptyError").show();
                break;

        }
    }

    CheckError.prototype.clearErrorMessage = function(){
        $(".errorMessageAuth > div").hide();
    }

    CheckError.prototype.emptyFields = function(){
        this.clearErrorFields();
        var hasEmpty = false;
        $(".form-control").each(function(index, item){
            var $item = $(item);
            var itemValue = $item.val();
            if(itemValue == ""){
                $item.addClass("has-error");
                hasEmpty = true;
            }
        });
        return hasEmpty;
    }

    CheckError.prototype.comparePasswords = function(){
        this.clearErrorFields();

        var $password = $(".form-control[name='password']");
        var $confirmPassword = $(".form-control[name='confirm_password']");

        var passwordVal = $password.val();
        var confirmPasswordVal = $confirmPassword.val();
        if(passwordVal === confirmPasswordVal){
            return true;
        }
        return false;
    }


    var checkForm = new InstallForm();
})
