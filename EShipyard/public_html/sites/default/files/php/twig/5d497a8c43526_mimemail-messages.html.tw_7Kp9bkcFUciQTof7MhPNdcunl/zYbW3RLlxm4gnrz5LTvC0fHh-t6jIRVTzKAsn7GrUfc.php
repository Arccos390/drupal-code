<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* themes/custom/shipyard/templates/mimemail-messages.html.twig */
class __TwigTemplate_9575d2619742cf343f51006464389a887380bb19598eb6afc6ab6aecdcd516f7 extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $tags = ["if" => 136, "set" => 144];
        $filters = ["escape" => 139, "length" => 144, "raw" => 150];
        $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if', 'set'],
                ['escape', 'length', 'raw'],
                []
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->getSourceContext());

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 1
        echo "<html>
  <head>

    <style>
      h3 {color:#0a6363;
        max-width: 700px;
        width: 100%;
        margin: 0 auto;
        font-weight: 300;
        text-align: center;
        padding-top: 20px;}
      h2 {
        text-align: center;
        max-width: 700px;
        width: 100%;
        margin: 0 auto;
        border-bottom: 1px solid #d4d4d4;
        padding: 20px 0;
        margin-top: 40px;
        color: black;
      }
      h1 {color:black;}
      p {
        color: #000000;
        padding:  0 20px;
      }
      #mimemail-body {
        background: #eaefed !important;
        color: black !important;
      }
      #center {
        max-width: 960px;
        width: 100%;
        margin: 0 auto;
        background: white;
      }
      ul {
        max-width: 600px;
        margin: 0 auto;
        padding: 0 20px;
      }
      .header {
        display: block;
        width: 100%;
        background: #0d5959;
        padding: 5px 0;
      }

      .header img {
        display: block;
        margin: 0 auto;
        padding: 0 20px;
      }
      .action-wrapper {
        text-align: center;
      }
      .action {
        display: block;
        width: 100%;
        text-align: center;
        color: black;
      }
      .btn {
        background: #e87915;
        padding: 8px 50px;
        border-radius: 30px;
        color: white !important;
        min-width: 200px;
        text-align: center;
        display: inline-block;
        margin: 0 auto;
        margin-top: 20px;
      }
      .benefits {
        max-width: 700px;
        width: 100%;
        margin: 0 auto;
        font-size: 16px!important;
        background: white;
        padding: 20px 0;
      }

      .benefits p {
        color: #0a6363;
        font-weight: 600;
        text-align: center;
      }

      li {
        color: black;
        margin-bottom: 10px;
      }

      .mail-wrapper a {
        color: #0a6363;
      }

      .next-login-wrapper {
        max-width: 600px;
        margin: 0 auto;
        margin-top: 60px;
        border-top: 1px solid #ddd;
        padding-top: 20px;
        text-align: center;
      }

      .next-login-wrapper .next-login-text {
        font-size:  16px;
        color: #0a6363;
      }

      .mail-footer {
        background: #dddddd;
        padding: 10px 20px 8px;
        margin-top: 60px;
        text-align: center;
        color: #000000;
      }

      .mail-footer a {
        color: #000000;
      }

      .mail-footer .address {
        margin-top: 10px;
      }

      .mail-footer span {
        display: inline-block;
        margin-right: 10px;
      }

    </style>

      <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
      ";
        // line 136
        if (($context["css"] ?? null)) {
            // line 137
            echo "      <style type=\"text/css\">
          <!--
          ";
            // line 139
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["css"] ?? null)), "html", null, true);
            echo "
         -->
      </style>
      ";
        }
        // line 143
        echo "  </head>
  <body id=\"mimemail-body\" ";
        // line 144
        if (((twig_length_filter($this->env, ($context["module"] ?? null)) > 0) && (twig_length_filter($this->env, ($context["key"] ?? null)) > 0))) {
            echo "  ";
            $context["class"] = (((("class=\"" . $this->sandbox->ensureToStringAllowed(($context["module"] ?? null))) . "-") . $this->sandbox->ensureToStringAllowed(($context["key"] ?? null))) . "\"");
            echo " ";
        }
        echo " >
    <div id=\"center\">
      <div id=\"main\">
        <div class=\"header\">
        <img src=\"https://bsg.e-shipyard.gr/themes/custom/shipyard/images/mail/mail-logo-3.png\" alt=\"LOGO\"/>
        </div>
        ";
        // line 150
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->renderVar($this->sandbox->ensureToStringAllowed(($context["body"] ?? null)));
        echo "
      </div>
    </div>
  </body>
</html>


";
    }

    public function getTemplateName()
    {
        return "themes/custom/shipyard/templates/mimemail-messages.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  221 => 150,  208 => 144,  205 => 143,  198 => 139,  194 => 137,  192 => 136,  55 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("", "themes/custom/shipyard/templates/mimemail-messages.html.twig", "/home/bsgeship/public_html/themes/custom/shipyard/templates/mimemail-messages.html.twig");
    }
}
